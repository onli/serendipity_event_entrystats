<?php

if (IN_serendipity !== true) {
    die ("Don't hack!");
}


// Probe for a language include with constants. Still include defines later on, if some constants were missing
$probelang = dirname(__FILE__) . '/' . $serendipity['charset'] . 'lang_' . $serendipity['lang'] . '.inc.php';
if (file_exists($probelang)) {
    include $probelang;
}

include dirname(__FILE__) . '/lang_en.inc.php';

class serendipity_event_entrystats extends serendipity_event {
    var $title = PLUGIN_EVENT_ENTRYSTATS_NAME;

    function introspect(&$propbag) {
        global $serendipity;

        $propbag->add('name',          PLUGIN_EVENT_ENTRYSTATS_NAME);
        $propbag->add('description',   PLUGIN_EVENT_ENTRYSTATS_DESC);
        $propbag->add('stackable',     false);
        $propbag->add('author',        'Malte Paskuda');
        $propbag->add('version',       '0.1');
        $propbag->add('requirements',  array(
            'serendipity' => '0.8'
        ));
        $propbag->add('event_hooks',   array('frontend_display' => true));
        $propbag->add('groups', array('MARKUP'));
    }

    function generate_content(&$title) {
        $title = $this->title;
    }


    /*function introspect_config_item($name, &$propbag) {
        
    }*/

    function install() {
        $this->setupDB();
    }

    function setupDB() {
        global $serendipity;
        $sql = "CREATE TABLE IF NOT EXISTS
                {$serendipity['dbPrefix']}entrystats (
                    id int(11) NOT NULL ,
                    views MEDIUMINT {UNSIGNED} NOT NULL DEFAULT 1,
                    PRIMARY KEY(id)
                )";
        serendipity_db_schema_import($sql);
    }


    function event_hook($event, &$bag, &$eventData) {
        global $serendipity;

        $hooks = &$bag->get('event_hooks');

        if (isset($hooks[$event])) {
            switch($event) {
                case 'frontend_display':
                    //make sure it is an entry, not a comment
                    if (isset($eventData['comments'])) {
                        $this->countPageView($eventData['id']);
                    }
                    return true;
                    break;

                default:
                    return false;
            }
        } else {
            return false;
        }
    }

    function  countPageView($id) {
        global $serendipity;
        $sql = "INSERT INTO
                    {$serendipity['dbPrefix']}entrystats (id)
                VALUES
                    ($id)
                ON DUPLICATE KEY
                    UPDATE
                        views = views + 1";
        serendipity_db_query($sql);
    }

}

/* vim: set sts=4 ts=4 expandtab : */
?>