<?php

require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
require_once ABSPATH . 'wp-admin/includes/template.php';

/**
 * Display all plugins in a table
 */
class WP_Status_Table extends WP_List_Table
{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();

        $data = $this->table_data();

        $this->_column_headers = array($columns);
        $this->items = $data;
    }

    /**
     * Columns in the table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'name' => 'Plugin',
            'version' => 'Version'
        );
        return $columns;
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    public function table_data() {
        $plugins = array();
        // Get all plugins
        $all_plugins = apply_filters( 'all_plugins', get_plugins() );

        // Get active plugins
        $active_plugins = get_option('active_plugins');

        // Assemble array of name, version, and whether plugin is active (boolean)
        foreach ( $all_plugins as $key => $value ) {
            $plugins[ $key ] = array(
                'name'    => $value['Name'],
                'version' => $value['Version'],
            );
        }

        return $plugins;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'name':
                return '<strong>'.$item[$column_name].'</strong>';
            case 'version':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }
}
