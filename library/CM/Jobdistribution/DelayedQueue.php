<?php

class CM_Jobdistribution_DelayedQueue implements CM_Service_ManagerAwareInterface {

    use CM_Service_ManagerAwareTrait;

    /**
     * @param CM_Service_Manager $serviceManager
     */
    public function __construct(CM_Service_Manager $serviceManager = null) {
        if ($serviceManager) {
            $this->setServiceManager($serviceManager);
        }
    }

    /**
     * @param CM_Jobdistribution_Job_Abstract $job
     * @param array                           $params
     * @param int                             $delay
     */
    public function addJob(CM_Jobdistribution_Job_Abstract $job, array $params, $delay) {
        CM_Db_Db::insert('cm_jobdistribution_delayedqueue', [
            'className' => get_class($job),
            'params'    => CM_Params::encode($params, true),
            'executeAt' => time() + (int) $delay,
        ]);
    }

    /**
     * @param CM_Jobdistribution_Job_Abstract $job
     * @param array                           $params
     */
    public function cancelJob(CM_Jobdistribution_Job_Abstract $job, array $params) {
        CM_Db_Db::delete('cm_jobdistribution_delayedqueue', [
            'className' => get_class($job),
            'params'    => CM_Params::encode($params, true),
        ]);
    }

    /**
     * @param CM_Jobdistribution_Job_Abstract $job
     * @param array                           $params
     * @return int
     */
    public function countJob(CM_Jobdistribution_Job_Abstract $job, array $params) {
        return CM_Db_Db::count('cm_jobdistribution_delayedqueue', [
            'className' => get_class($job),
            'params'    => CM_Params::encode($params, true),
        ]);
    }

    public function queueOutstanding() {
        $executeAtMax = time();
        $result = CM_Db_Db::select('cm_jobdistribution_delayedqueue', '*', '`executeAt` <= ' . $executeAtMax, '`executeAt` ASC');
        while ($row = $result->fetch()) {
            $jobName = $row['className'];
            $job = $this->_instantiateJob($jobName);
            if ($job) {
                $params = null;
                $paramsEncoded = $row['params'];
                try {
                    $params = CM_Params::decode($paramsEncoded, true);
                } catch (Exception $ex) {
                    $context = new CM_Log_Context();
                    $context->setException($ex);
                    $context->setExtra(['job' => $jobName, 'paramsEncoded' => $paramsEncoded]);
                    $this->getServiceManager()->getLogger()->warning('Job-params could not be decoded', $context);
                    continue;
                }
                $job->queue($params);
            }
        }
        CM_Db_Db::delete('cm_jobdistribution_delayedqueue', '`executeAt` <= ' . $executeAtMax);
    }

    /**
     * @param string $className
     * @return CM_Jobdistribution_Job_Abstract|null
     */
    protected function _instantiateJob($className) {
        try {
            /** @var CM_Jobdistribution_Job_Abstract $job */
            $job = new $className();
            if ($job instanceof CM_Service_ManagerAwareInterface) {
                /** @var CM_Service_ManagerAwareInterface $job */
                $job->setServiceManager($this->getServiceManager());
            }
            return $job;
        } catch (Exception $e) {
            $logLevel = CM_Log_Logger::exceptionToLevel($e);
            $context = new CM_Log_Context();
            $context->setException($e);
            $this->getServiceManager()->getLogger()->addMessage('Delayed queue error', $logLevel, $context);
            return null;
        }
    }
}
