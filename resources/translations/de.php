<?php

return function (CM_Model_Language $language) {
    $language->setTranslation('You', 'Du');
    $language->setTranslation('Ok', 'Ok');
    $language->setTranslation('Cancel', 'Abbrechen');
    $language->setTranslation('Confirmation', 'Rückfrage');
    $language->setTranslation('{$label} is required.', '{$label} wird benötigt.', array('label'));
    $language->setTranslation('Required', 'Benötigt');
    $language->setTranslation('Please Confirm', 'Bitte bestätigen');
    $language->setTranslation('Year', 'Jahr');
    $language->setTranslation('Month', 'Monat');
    $language->setTranslation('Day', 'Tag');
    $language->setTranslation('.date.month.1', 'Januar');
    $language->setTranslation('.date.month.2', 'Februar');
    $language->setTranslation('.date.month.3', 'März');
    $language->setTranslation('.date.month.4', 'April');
    $language->setTranslation('.date.month.5', 'Mai');
    $language->setTranslation('.date.month.6', 'Juni');
    $language->setTranslation('.date.month.7', 'Juli');
    $language->setTranslation('.date.month.8', 'August');
    $language->setTranslation('.date.month.9', 'September');
    $language->setTranslation('.date.month.10', 'Oktober');
    $language->setTranslation('.date.month.11', 'November');
    $language->setTranslation('.date.month.12', 'Dezember');
    $language->setTranslation('.date.timeago.prefixAgo', 'vor');
    $language->setTranslation('.date.timeago.prefixFromNow', 'in');
    $language->setTranslation('.date.timeago.suffixAgo', '');
    $language->setTranslation('.date.timeago.suffixFromNow', '');
    $language->setTranslation('.date.timeago.seconds', 'wenigen Sekunden');
    $language->setTranslation('.date.timeago.minute', 'etwa einer Minute');
    $language->setTranslation('.date.timeago.minutes', '{$count} Minuten');
    $language->setTranslation('.date.timeago.hour', 'etwa einer Stunde');
    $language->setTranslation('.date.timeago.hours', '{$count} Stunden');
    $language->setTranslation('.date.timeago.day', 'etwa einem Tag');
    $language->setTranslation('.date.timeago.days', '{$count} Tagen');
    $language->setTranslation('.date.timeago.month', 'etwa einem Monat');
    $language->setTranslation('.date.timeago.months', '{$count} Monaten');
    $language->setTranslation('.date.timeago.year', 'etwa einem Jahr');
    $language->setTranslation('.date.timeago.years', '{$count} Jahren');
    $language->setTranslation('The content you tried to interact with has been deleted.', 'Dieser Inhalt wurde gelöscht.');
    $language->setTranslation('Your browser is no longer supported. Click here to upgrade…', 'Dein Browser wird nicht mehr unterstützt. Klicke hier um ihn zu aktualisieren…');
    $language->setTranslation('You can only select {$cardinality} items.', 'Maximal {$cardinality} Element.', array('cardinality'));
    $language->setTranslation('{$file} has an invalid extension. Only {$extensions} are allowed.', '{$file} hat eine ungültige Dateiendung. Nur {$extensions} werden unterstützt.', array('file',
        'extensions'));
    $language->setTranslation('Drag files here', 'Ziehe deine Datein hierhin');
    $language->setTranslation('or', 'oder');
    $language->setTranslation('Upload Files', 'Datein hochladen');
};