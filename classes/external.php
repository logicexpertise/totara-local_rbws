<?php

/**
 *
 * @package     local_rbws
 * @subpackage
 * @copyright   2019 Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @author      Olumuyiwa Taiwo {@link https://moodle.org/user/view.php?id=416594}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rbws;

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use external_warnings;
use reportbuilder;

class external extends external_api {

    protected static $report = [];

    public static function create_report($id) { // TODO: Exception handling, capability checks
        if (!isset(static::$report[$id])) {
            try {
                static::$report[$id] = new reportbuilder($id);
            } catch (\moodle_exception $e) {
                static::$report[$id] = false;
            }
        }
        return static::$report[$id];
    }

    public static function get_report_by_id_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'The report ID', VALUE_REQUIRED),
        ]);
    }

    public static function get_report_by_id_returns() {

        return new external_single_structure([
            'info' => new external_single_structure(
                    [
                'fullname' => new external_value(PARAM_RAW, 'The report fullname', VALUE_OPTIONAL),
                'shortname' => new external_value(PARAM_RAW, 'The report shortname', VALUE_OPTIONAL),
                'description' => new external_value(PARAM_RAW, 'The report description', VALUE_OPTIONAL),
                'source' => new external_VALUE(PARAM_RAW, 'The report builder source', VALUE_OPTIONAL),
                    ]),
            'rows' => self::get_report_description(),
            'warnings' => new external_warnings(),
        ]);
    }

    public static function get_report_by_id($id) {
        global $DB;
        $wsparams = self::validate_parameters(self::get_report_by_id_parameters(), array('id' => $id));

        $wsparams['id'] = $id;

        $results = [];
        $warnings = [];
        if (!$report = self::create_report($wsparams['id'])) {
            $warnings[] = [
                'item' => 'error',
                'warningcode' => 'createerror',
                'itemid' => $wsparams['id'],
                'message' => 'error generating report with id ' . $wsparams['id'],
            ];
        } else if (!reportbuilder::is_capable($wsparams['id'])) {
            $warnings[] = [
                'item' => 'error',
                'warningcode' => 'nocapability',
                'itemid' => $wsparams['id'],
                'message' => 'no capability to view report with id ' . $wsparams['id'],
            ];
        } else {
            $columns = $report->columns;

            $fields = [];
            foreach ($columns as $column) {
                if ($column->display_column(true)) {
                    $name = $report->format_column_heading($column, true);
                    $fields[] = $name;
                }
            }

            list($query, $reportparams) = $report->build_query();
            if ($records = $DB->get_recordset_sql($query, $reportparams)) {
                $rows = [];
                foreach ($records as $record) {
                    $rows[] = $report->src->process_data_row($record, 'rbws', $report);
                }
                $records->close();
            }

            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $data = array_combine($fields, $row);
                    $results[] = $data;
                }
            }
            $warnings[] = [
                'warningcode' => 'rowcount',
                'item' => count($results),
                'itemid' => $wsparams['id'],
                'message' => count($results) . ' rows returned for report with id ' . $wsparams['id'],
            ];
        }

        return [
            'info' => [
                'fullname' => clean_text($report->fullname),
                'shortname' => clean_text($report->shortname),
                'description' => clean_text($report->description),
                'source' => clean_text($report->src->sourcetitle),
            ],
            'rows' => $results,
            'warnings' => $warnings,
        ];
    }

    protected static function get_report_description() {
        try {
            $id = required_param('id', PARAM_INT);
            $params = self::validate_parameters(self::get_report_by_id_parameters(), array('id' => $id));
            $params['id'] = $id;
            $report = self::create_report($params['id']);
            $columns = $report->columns;

            $headings = [];
            foreach ($columns as $column) {
                if ($column->display_column()) {
                    $name = $report->format_column_heading($column, true);
                    $headings[$name] = new external_value(PARAM_RAW, '', VALUE_OPTIONAL);
                }
            }
        } catch (\moodle_exception $ex) {
            $headings = []; // This is required so that API documentation displays in UI.
        }

        return new external_multiple_structure(
                new external_single_structure($headings, 'Report row, columns vary depending on report'),
                'Report rows'
        );
    }

}
