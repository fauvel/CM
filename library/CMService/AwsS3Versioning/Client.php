<?php

class CMService_AwsS3Versioning_Client {

    /** @var Aws\S3\S3Client */
    private $_client;

    /** @var string */
    private $_bucket;

    /** @var CM_OutputStream_Interface */
    private $_output;

    /**
     * @param Aws\S3\S3Client                $client
     * @param string                         $bucket
     * @param CM_OutputStream_Interface|null $output
     */
    public function __construct(Aws\S3\S3Client $client, $bucket, CM_OutputStream_Interface $output = null) {
        if (null === $output) {
            $output = new CM_OutputStream_Stream_StandardOutput();
        }
        $this->_client = $client;
        $this->_bucket = (string) $bucket;
        $this->_output = $output;
        $this->_assertVersioningEnabled();
    }

    /**
     * @return bool
     */
    public function getVersioningEnabled() {
        $result = $this->_client->getBucketVersioning(array(
            'Bucket' => $this->_bucket,
        ));
        return 'Enabled' == $result->get('Status');
    }

    /**
     * @param string $prefix
     * @return CMService_AwsS3Versioning_Response_Version[]
     */
    public function getVersions($prefix) {
        $options = array(
            'Bucket' => $this->_bucket,
            'Prefix' => (string) $prefix,
        );
        $versionList = array();
        foreach ($this->_client->getListObjectVersionsIterator($options) as $data) {
            $versionList[] = new CMService_AwsS3Versioning_Response_Version($data);
        }
        usort($versionList, function (CMService_AwsS3Versioning_Response_Version $a, CMService_AwsS3Versioning_Response_Version $b) {
            if ($a->getLastModified() == $b->getLastModified()) {
                return 0;
            }
            return $a->getLastModified() < $b->getLastModified() ? 1 : -1;
        });
        return $versionList;
    }

    /**
     * @param string   $key
     * @param DateTime $date
     */
    public function restoreByDeletingNewerVersions($key, DateTime $date) {
        $versions = $this->getVersions($key);
        $versionsToDelete = Functional\select($versions, function (CMService_AwsS3Versioning_Response_Version $version) use ($key, $date) {
            $isExactKeyMatch = ($key === $version->getKey());
            $isModifiedAfter = ($date < $version->getLastModified());
            return $isExactKeyMatch && $isModifiedAfter;
        });
        $this->_output->writeln('Restoring `' . $key . '` - deleting ' . count($versionsToDelete) . ' versions...');
        $objectsData = Functional\map($versionsToDelete, function (CMService_AwsS3Versioning_Response_Version $version) {
            return array('Key' => $version->getKey(), 'VersionId' => $version->getId());
        });
        $this->_client->deleteObjects(array(
            'Bucket'  => $this->_bucket,
            'Objects' => $objectsData,
        ));
    }

    /**
     * @param string   $key
     * @param DateTime $date
     */
    public function restoreByCopyingOldVersion($key, DateTime $date) {
        $versions = $this->getVersions($key);
        /** @var CMService_AwsS3Versioning_Response_Version $versionToRestore */
        $versionToRestore = Functional\first($versions, function (CMService_AwsS3Versioning_Response_Version $version) use ($key, $date) {
            $isExactKeyMatch = ($key === $version->getKey());
            $isModifiedBeforeOrAt = ($date >= $version->getLastModified());
            return $isExactKeyMatch && $isModifiedBeforeOrAt;
        });
        $hasNoPriorVersion = (!$versionToRestore);
        $restoreVersionIsDeleteMarker = ($versionToRestore && null === $versionToRestore->getETag() && null === $versionToRestore->getSize());
        if ($hasNoPriorVersion || $restoreVersionIsDeleteMarker) {
            $this->_client->deleteObject(array(
                'Bucket' => $this->_bucket,
                'Key'    => $key,
            ));
        } else {
            $this->_client->copyObject(array(
                'Bucket'     => $this->_bucket,
                'CopySource' => urlencode($this->_bucket . '/' . $key) . '?versionId=' . $versionToRestore->getId(),
                'Key'        => $key,
            ));
        }
    }

    private function _assertVersioningEnabled() {
        if (!$this->getVersioningEnabled()) {
            throw new CM_Exception_Invalid('Versioning is not enabled on bucket `' . $this->_bucket . '`.');
        }
    }
}
