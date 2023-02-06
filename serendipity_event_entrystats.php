<?php

if (IN_serendipity !== true) {
    die ("Don't hack!");
}


@serendipity_plugin_api::load_language(dirname(__FILE__));

class serendipity_event_entrystats extends serendipity_event {
    var $title = PLUGIN_EVENT_ENTRYSTATS_NAME;

    function introspect(&$propbag) {
        global $serendipity;

        $propbag->add('name',          PLUGIN_EVENT_ENTRYSTATS_NAME);
        $propbag->add('description',   PLUGIN_EVENT_ENTRYSTATS_DESC);
        $propbag->add('stackable',     false);
        $propbag->add('author',        'Malte Paskuda');
        $propbag->add('version',       '0.2');
        $propbag->add('requirements',  array(
            'serendipity' => '0.8'
        ));
        $propbag->add('event_hooks',   array(
                                            'entry_display' => true,
                                            'backend_sidebar_entries' => true,
                                            'backend_sidebar_entries_event_display_entrystats'  => true,
                                        ));
        $propbag->add('groups', array('STATISTICS'));
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


    function event_hook($event, &$bag, &$eventData, $addData = null) {
        global $serendipity;

        $hooks = &$bag->get('event_hooks');

        if (isset($hooks[$event])) {
            switch($event) {
                case 'entry_display':
                    //make sure it is an entry, not a comment, and just one entry
                    if (count($eventData) != 1) {
                        break;
                    }
                    if (isset($eventData[0]['comments'])) {
                        $this->countPageView($eventData[0]['id']);
                    }
                    return true;
                    break;

                case 'backend_sidebar_entries':
?>
                            <li><a href="?serendipity[adminModule]=event_display&amp;serendipity[adminAction]=entrystats"><?php echo PLUGIN_EVENT_ENTRYSTATS_NAME; ?></a></li>
<?php
                    return true;
                    break;

                case 'backend_sidebar_entries_event_display_entrystats':
                    $this->displayStats();

                    return true;
                    break;

                default:
                    return false;
            }
        } else {
            return false;
        }
    }

    function countPageView($id) {
        global $serendipity;
        
        if (stristr($serendipity['dbType'], 'sqlite')) {
            $sql = "INSERT INTO
                        {$serendipity['dbPrefix']}entrystats (id)
                    VALUES
                        ($id)
                    ON CONFLICT(id)
                        DO UPDATE SET
                            views = views + 1";

        } else {
              $sql = "INSERT INTO
                        {$serendipity['dbPrefix']}entrystats (id)
                    VALUES
                        ($id)
                    ON DUPLICATE KEY
                        UPDATE
                            views = views + 1";
        }
        
        serendipity_db_query($sql);
    }

    function displayStats() {
        global $serendipity;
        
        $sql = "SELECT
                    title, views FROM
                {$serendipity['dbPrefix']}entries AS e
                LEFT JOIN
                        {$serendipity['dbPrefix']}entrystats AS s
                ON
                    e.id = s.id
                ORDER BY
                    views";
        $stats = serendipity_db_query($sql);

        $title_str = $CONST.TITLE;
        $view_str = $CONST.PLUGIN_EVENT_ENTRYSTATS_VIEWS;
        
        $html = "<table border=1><thead>
        <tr>
            <td>$title_str</td>
            <td>$view_str</td>
        </tr>
        </thead>
        <tbody>";
        foreach ($stats as $row) {
            $html .= "<tr>
            <td>${row['title']}</td>
            <td style='text-align:right'>${row['views']}</td>
            </tr>";
        }
        $html .= "</tbody></table>";
        echo $html;
    }

}

/* vim: set sts=4 ts=4 expandtab : */
?>