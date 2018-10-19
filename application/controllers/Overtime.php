<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Overtime extends MY_Controller
{
    
    public function __construct()
    {
        parent::__construct();
        
        $this->load->database();
    }
    
    public function pass_clock()
    {
        set_time_limit(0);
        
        $year = isset($_GET['year']) ? $_GET['year'] : date('Y');
        $c_id = isset($_GET['c']) ? $_GET['c'] : null;
        
        $sql = '
            SELECT DISTINCT work_board.c_id, work_board.d_id
            FROM work_board
            INNER JOIN working_session ON working_session.working_date = work_board.w_date AND working_session.remove = 0
            INNER JOIN workers ON workers.c_id = work_board.c_id AND workers.d_id = work_board.d_id AND workers.worker_id = working_session.worker_id
            WHERE work_board.w_date LIKE "%' . $year . '%"' . ($c_id ? (' AND work_board.c_id = ' . $c_id) : '');
        
        $query = $this->db->query($sql);
        $companies = $query->result();
        
        $c_id = null;
        foreach ($companies as $company) {
            if ($company->c_id == $c_id) {
                continue;
            }
            $c_id = $company->c_id;
            
            $file = "/home/asbdev/public_html/memberdata/"  . $company->c_id . "/info/presets.xml";
            if (is_file($file)) {
                $companyXML = simplexml_load_file($file);
                if ($this->getPresetData($companyXML, 'taskTracker->department_' . $company->d_id . '->startweek') !== false) {
                    $startofWeek = $this->getPresetData($companyXML, 'taskTracker->department_' . $company->d_id . '->startweek');
                } else {
                    $startofWeek = $this->getPresetData($companyXML, 'taskTracker->startweek');
                }
                
                if (! $startofWeek) {
                    $startofWeek = 0;
                }
            } else {
                $startofWeek = 0;
            }
            
            $dayoftheWeek = date('w', strtotime($year . '-01-01'));
            $dayoftheWeek = $dayoftheWeek < $startofWeek ? ($dayoftheWeek + 7) : $dayoftheWeek;
            $startdate = date('Y-m-d', strtotime(($startofWeek - $dayoftheWeek) . " day", strtotime($year . '-01-01')));
            
            while (intval(date('Y', strtotime($startdate))) <= intval($year)) {
                if (intval(date('Y', strtotime($startdate))) == intval($year)) {
                    $this->db->select('*');
                    $this->db->from('overtime_clock_cron');
                    $this->db->where('c_id', $company->c_id);
                    $this->db->where('start_date', $startdate);
                    $query = $this->db->get();
                    
                    if (!$query->result()) {
                        $this->db->insert('overtime_clock_cron', [
                            'c_id' => $company->c_id,
                            'start_date' => $startdate
                        ]);
                    }
                }
                $startdate = date('Y-m-d', strtotime("+7 day", strtotime($startdate)));
            }
            
        }
    }
    
    /**
     * Load workboard
     */
    public function index()
    {
        set_time_limit(0);
        
        $this->db->select('overtime_cron.c_id, overtime_cron.start_date, companies.timezone');
        $this->db->from('overtime_cron');
        $this->db->join('companies', 'companies.company_id = overtime_cron.c_id', 'INNER');
        $this->db->order_by('overtime_cron.c_id, overtime_cron.start_date');
        $this->db->limit(100);
        
        $query = $this->db->get();
        $companies = $query->result();
        
        if ($companies) {
            foreach ($companies as $company) {
                $this->update_overtime($company->c_id, $company->start_date, $company->timezone);
                
                $this->db->delete('overtime_cron', array('c_id' => $company->c_id, 'start_date' => $company->start_date));
            }
        }
        echo 'Successfull';
    }
    
    /**
     * Load workboard
     */
    public function by_clock()
    {
        set_time_limit(0);
        
        $this->db->select('overtime_clock_cron.c_id, overtime_clock_cron.start_date, companies.timezone');
        $this->db->from('overtime_clock_cron');
        $this->db->join('companies', 'companies.company_id = overtime_clock_cron.c_id', 'INNER');
        $this->db->order_by('overtime_clock_cron.c_id, overtime_clock_cron.start_date');
        $this->db->limit(100);
        
        $query = $this->db->get();
        $companies = $query->result();
        
        if ($companies) {
            foreach ($companies as $company) {
                $this->update_emp_overtime($company->c_id, $company->start_date, $company->timezone);
                
                $this->db->delete('overtime_clock_cron', array('c_id' => $company->c_id, 'start_date' => $company->start_date));
            }
        }
        echo 'Successfull';
    }
    
    private function update_emp_overtime($c_id, $startdate, $timezone)
    {
        $rowsAffected = 0;
        
        if (!$timezone) {
            $file = "/home/asbdev/public_html/memberdata/"  . $c_id . "/info/presets.xml";
            if (is_file($file)) {
                $companyXML = simplexml_load_file($file);
                $timezone = $this->getPresetData($companyXML, 'default->weather->timezone');
            }
        }
        if (!$timezone) {
            $timezone = 'America/Denver';
        }
        
        date_default_timezone_set($timezone);
        
        $rowsAffected += $this->emp_holiday($c_id, $startdate);
        
        $rowsAffected += $this->emp_regular($c_id, $startdate);
        
        return $rowsAffected;
    }
    
    private function update_overtime($c_id, $startdate, $timezone)
    {
        $rowsAffected = 0;
        
        if (!$timezone) {
            $file = "/home/asbdev/public_html/memberdata/"  . $c_id . "/info/presets.xml";
            if (is_file($file)) {
                $companyXML = simplexml_load_file($file);
                $timezone = $this->getPresetData($companyXML, 'default->weather->timezone');
            }
        }
        if (!$timezone) {
            $timezone = 'America/Denver';
        }
        
        date_default_timezone_set($timezone);
        
        $rowsAffected += $this->holiday($c_id, $startdate);
        
        $rowsAffected += $this->regular($c_id, $startdate);
        
        $sql = "
            DELETE work_board_task_hours FROM work_board_task_hours
            INNER JOIN work_board ON work_board.work_board_id = work_board_task_hours.work_board_id
            WHERE work_board.c_id = " . $c_id . "
            AND (work_board.w_date BETWEEN '" . $startdate . "' AND DATE_ADD('" . $startdate . "', INTERVAL +6 DAY))
            AND NOT EXISTS (
                SELECT * FROM  work_board_task
                WHERE work_board_task.wb_task_id = work_board_task_hours.wb_task_id
            )
        ";
        $this->db->query($sql);
        
        return $rowsAffected;
    }
    
    private function regular($c_id, $startdate)
    {
        $rowsAffected = 0;
        $sql = "
            SELECT work_board.w_date, work_board_task.wb_task_id, work_board_task.worker_id, work_board.c_id, work_board.d_id,
                IF(work_board_task.wage > 0, work_board_task.wage, workers.wage) AS wage, work_board_task.est_overtime,
                work_board_task.est_hr, work_board.workboard_hours, work_board_task.task_id, work_board.work_board_id
            FROM work_board_task
            INNER JOIN workers ON workers.worker_id = work_board_task.worker_id
            INNER JOIN work_board ON work_board_task.work_board_id = work_board.work_board_id
            WHERE work_board.c_id = " . $c_id . " AND work_board_task.task_id >= 0
            AND (work_board.w_date BETWEEN '" . $startdate . "' AND DATE_ADD('" . $startdate . "', INTERVAL +6 DAY))
            AND NOT EXISTS (
                SELECT holidays.date
                FROM holidays
                WHERE holidays.c_id = work_board.c_id AND work_board.d_id = holidays.d_id AND holidays.date = SUBSTRING(work_board.w_date, 6)
            )
            ORDER BY work_board_task.worker_id, work_board.w_date, IF(task_id >= 0, 1, 2), work_board_task.sortorder
        ";
        $task_query = $this->db->query($sql);
        $tasks = $task_query->result_array();
        
        if ($tasks) {
            $e_tasks = array();
            foreach ($tasks as $task) {
                if (!isset($e_tasks[$task['worker_id']])) {
                    $e_tasks[$task['worker_id']] = array();
                }
                if (!isset($e_tasks[$task['worker_id']][$task['w_date']])) {
                    $e_tasks[$task['worker_id']][$task['w_date']] = array();
                }
                $e_tasks[$task['worker_id']][$task['w_date']][] = $task;
            }
            
            $week_days = array(1 => 'mon', 2 => 'tue', 3 => 'wed', 4 => 'thu', 5 => 'fri', 6 => 'sat', 7 => 'sun');
            
            foreach ($e_tasks as $worker_id => $days) {
                $ot_card = $this->get_overtime_card($worker_id);
                
                $rowtoupdate = array();
                $week_hrs = 0;
                $regular_hour_week = $ot_card['regular_hour_week'];
                foreach ($days as $day => $jobs) {
                    $day_of_week = date('N', strtotime($day));
                    $regular_hour = $ot_card['regular_hour_' . $week_days[$day_of_week]];
                    
                    $day_hrs = 0;
                    foreach ($jobs as $job) {
                        $job['ot_day_multiplier_new'] = $ot_card['overtime_rate_' . $week_days[$day_of_week]];
                        $job['ot_week_multiplier_new'] = $ot_card['overtime_rate_week'];
                        
                        if ($job['est_hr'] >= 0) { // if the est_hr is positive then do this
                            // OT by day
                            if (!empty($regular_hour) && $regular_hour >= 0 && !empty($job['ot_day_multiplier_new'])) {
                                $day_hrs += $job['est_hr'];
                                if ($day_hrs > $regular_hour) {
                                    if (($day_hrs - $regular_hour) > $job['est_hr']) {
                                        // over time greater than the job so apply all job hours to overtime
                                        $job['ot_day_hr_new'] = $job['est_hr'];
                                    } else {
                                        // only a portion on the hours fall into over time so only assign the portional hours;
                                        $job['ot_day_hr_new'] = $day_hrs - $regular_hour;
                                    }
                                } else {
                                    // no overtime found
                                    $job['ot_day_hr_new'] = 0;
                                }
                            }
                            
                            // OT by week
                            if ($regular_hour_week > 0 && $job['ot_week_multiplier_new'] > 0) {
                                $week_hrs += $job['est_hr'];
                                if ($week_hrs > $regular_hour_week) {
                                    if (($week_hrs - $regular_hour_week) > $job['est_hr']) {
                                        // over time greater than the job so apply all job hours to overtime
                                        $job['ot_week_hr_new'] = $job['est_hr'];
                                    } else {
                                        // only a portion on the hours fall into over time so only assign the portional hours;
                                        $job['ot_week_hr_new'] = $week_hrs - $regular_hour_week;
                                    }
                                } else {
                                    // no overtime found
                                    $job['ot_week_hr_new'] = 0;
                                }
                            }
                            
                        } else {// if the est_hr is negitive then do this
                            // OT by day
                            if (!empty($regular_hour) && $regular_hour >= 0 && !empty($job['ot_day_multiplier_new'])) {
                                if ($day_hrs > $regular_hour) {
                                    // overtime
                                    if (-($day_hrs - $regular_hour) < $job['est_hr']) {
                                        // over time greater than the job so apply all job hours to overtime
                                        $job['ot_day_hr_new'] = $job['est_hr'];
                                    } else {
                                        // only a portion on the hours fall into over time so only assign the portional hours;
                                        $job['ot_day_hr_new'] = -($day_hrs - $regular_hour);
                                    }
                                } else {
                                    $job['ot_day_hr_new'] = 0;
                                }
                                $day_hrs += $job['est_hr'];
                            }
                            
                            // OT by week
                            if ($regular_hour_week > 0 && $job['ot_week_multiplier_new'] > 0) {
                                if ($week_hrs > $regular_hour_week) {
                                    // overtime
                                    if (-($week_hrs - $regular_hour_week) < $job['est_hr']) {
                                        // over time greater than the job so apply all job hours to overtime
                                        $job['ot_week_hr_new'] = $job['est_hr'];
                                    } else {
                                        // only a portion on the hours fall into over time so only assign the portional hours;
                                        $job['ot_week_hr_new'] = -($week_hrs - $regular_hour_week);
                                    }
                                } else {
                                    $job['ot_week_hr_new'] = 0;
                                }
                                $week_hrs += $job['est_hr'];
                            }
                        }
                        $rowtoupdate[] = $job;
                    }
                }
                
                if ($rowtoupdate) {
                    foreach ($rowtoupdate as $row) {
                        $fields = array(
                            'wb_task_id' => $row['wb_task_id'],
                            'worker_id' => $row['worker_id'],
                            'task_id' => $row['task_id'],
                            'work_board_id' => $row['work_board_id'],
                            'hours_worked' => $row['est_hr'],
                            'holiday_hours' => 0,
                            'holiday_ot_hours' => 0,
                            'holiday_pay' => 0,
                            'holiday_ot_pay' => 0
                        );
                        if (isset($row['ot_day_hr_new']) && isset($row['ot_week_hr_new'])) {
                            if ($row['ot_day_hr_new'] >= $row['ot_week_hr_new'] && $row['ot_day_multiplier_new'] >= $row['ot_week_multiplier_new']) {
                                $ot_hours = $row['ot_day_hr_new'] ? $row['ot_day_hr_new'] : 0;
                                $regular_hours = $row['est_hr'] > $ot_hours ? ($row['est_hr'] - $ot_hours) : 0;
                                $fields['ot_hours'] = $ot_hours;
                                $fields['ot_pay'] = $row['ot_day_multiplier_new'] * $ot_hours * $row['wage'];
                                $fields['regular_hours'] = $regular_hours;
                                $fields['regular_pay'] = $regular_hours * $row['wage'];
                            } else if ($row['ot_day_hr_new'] <= $row['ot_week_hr_new'] && $row['ot_day_multiplier_new'] <= $row['ot_week_multiplier_new']) {
                                $ot_hours = $row['ot_week_hr_new'] ? $row['ot_week_hr_new'] : 0;
                                $regular_hours = $row['est_hr'] > $ot_hours ? ($row['est_hr'] - $ot_hours) : 0;
                                $fields['ot_hours'] = $ot_hours;
                                $fields['ot_pay'] = $row['ot_week_multiplier_new'] * $ot_hours * $row['wage'];
                                $fields['regular_hours'] = $regular_hours;
                                $fields['regular_pay'] = $regular_hours * $row['wage'];
                            } else if ($row['ot_day_hr_new'] >= $row['ot_week_hr_new'] && $row['ot_day_multiplier_new'] < $row['ot_week_multiplier_new']) {
                                $ot_hours = $row['ot_day_hr_new'] ? $row['ot_day_hr_new'] : 0;
                                $regular_hours = $row['est_hr'] > $ot_hours ? ($row['est_hr'] - $ot_hours) : 0;
                                $fields['ot_hours'] = $ot_hours;
                                $fields['ot_pay'] = $row['ot_day_multiplier_new'] * ($row['ot_day_hr_new'] - $row['ot_week_hr_new']) * $row['wage'];
                                $fields['ot_pay'] += $row['ot_week_multiplier_new'] * $row['ot_week_hr_new'] * $row['wage'];
                                $fields['regular_hours'] = $regular_hours;
                                $fields['regular_pay'] = $regular_hours * $row['wage'];
                            } else if ($row['ot_day_hr_new'] <= $row['ot_week_hr_new'] && $row['ot_day_multiplier_new'] > $row['ot_week_multiplier_new']) {
                                $ot_hours = $row['ot_week_hr_new'] ? $row['ot_week_hr_new'] : 0;
                                $regular_hours = $row['est_hr'] > $ot_hours ? ($row['est_hr'] - $ot_hours) : 0;
                                $fields['ot_hours'] = $ot_hours;
                                $fields['ot_pay'] = $row['ot_week_multiplier_new'] * ($row['ot_week_hr_new'] - $row['ot_day_hr_new']) * $row['wage'];
                                $fields['ot_pay'] += $row['ot_day_multiplier_new'] * $row['ot_day_hr_new'] * $row['wage'];
                                $fields['regular_hours'] = $regular_hours;
                                $fields['regular_pay'] = $regular_hours * $row['wage'];
                            }
                        } else if (isset($row['ot_day_hr_new'])) {
                            $ot_hours = $row['ot_day_hr_new'] ? $row['ot_day_hr_new'] : 0;
                            $regular_hours = $row['est_hr'] > $ot_hours ? ($row['est_hr'] - $ot_hours) : 0;
                            $fields['ot_hours'] = $ot_hours;
                            $fields['ot_pay'] = $row['ot_day_multiplier_new'] * $ot_hours * $row['wage'];
                            $fields['regular_hours'] = $regular_hours;
                            $fields['regular_pay'] = $regular_hours * $row['wage'];
                        } else if (isset($row['ot_week_hr_new'])) {
                            $ot_hours = $row['ot_week_hr_new'] ? $row['ot_week_hr_new'] : 0;
                            $regular_hours = $row['est_hr'] > $ot_hours ? ($row['est_hr'] - $ot_hours) : 0;
                            $fields['ot_hours'] = $ot_hours;
                            $fields['ot_pay'] = $row['ot_week_multiplier_new'] * $ot_hours * $row['wage'];
                            $fields['regular_hours'] = $regular_hours;
                            $fields['regular_pay'] = $regular_hours * $row['wage'];
                        }
                        
                        $this->db->select('wb_task_id');
                        $this->db->from('work_board_task_hours');
                        $this->db->where('wb_task_id', $row['wb_task_id']);
                        $query = $this->db->get();
                        
                        if ($query->result()) {
                            $this->db->where('wb_task_id', $row['wb_task_id']);
                            $this->db->update('work_board_task_hours', $fields);
                        } else {
                            $this->db->insert('work_board_task_hours', $fields);
                        }
                        
                        $rowsAffected ++;
                    }
                }
            }
        }
        return $rowsAffected;
    }
    
    private function holiday($c_id, $startdate)
    {
        $rowsAffected = 0;
        $sql = "
            SELECT work_board_task.wb_task_id, work_board_task.worker_id, work_board_task.task_id, work_board_task.est_hr,
                COALESCE(overtime_card.holiday_rate, 2) AS holiday_rate, work_board.workboard_hours,
                IF(work_board_task.wage > 0, work_board_task.wage, workers.wage) AS wage, work_board.work_board_id
            FROM work_board_task
            INNER JOIN work_board ON work_board.work_board_id = work_board_task.work_board_id
            INNER JOIN workers ON workers.worker_id = work_board_task.worker_id
            LEFT OUTER JOIN overtime_card ON overtime_card.id = workers.overtime_card_id
            WHERE (work_board.w_date BETWEEN '" . $startdate . "' AND DATE_ADD('" . $startdate . "', INTERVAL +6 DAY))
            AND work_board.c_id = " . $c_id . " AND work_board_task.task_id >= 0
            AND EXISTS (
                SELECT holidays.date
                FROM holidays
                WHERE holidays.c_id = work_board.c_id AND work_board.d_id = holidays.d_id
                AND holidays.date = SUBSTRING(work_board.w_date, 6)
            )
        ";
        $holiday_query = $this->db->query($sql);
        $holiday_wb_tasks = $holiday_query->result();
        
        if ($holiday_wb_tasks) {
            foreach ($holiday_wb_tasks as $holiday_wb_task) {
                $holiday_hours = $holiday_wb_task->est_hr <= $holiday_wb_task->workboard_hours ? $holiday_wb_task->est_hr : $holiday_wb_task->workboard_hours;
                $holiday_ot_hours = $holiday_wb_task->est_hr > $holiday_wb_task->workboard_hours ? $holiday_wb_task->est_hr - $holiday_wb_task->workboard_hours : 0;
                $fields = array(
                    'worker_id' => $holiday_wb_task->worker_id,
                    'task_id' => $holiday_wb_task->task_id,
                    'work_board_id' => $holiday_wb_task->work_board_id,
                    'hours_worked' => $holiday_wb_task->est_hr,
                    'regular_hours' => 0,
                    'ot_hours' => 0,
                    'holiday_hours' => $holiday_hours,
                    'holiday_ot_hours' => $holiday_ot_hours,
                    'regular_pay' => 0,
                    'ot_pay' => 0,
                    'holiday_pay' => $holiday_wb_task->wage * $holiday_wb_task->holiday_rate * $holiday_hours,
                    'holiday_ot_pay' => $holiday_wb_task->wage * $holiday_wb_task->holiday_rate * $holiday_ot_hours
                );
                
                
                $this->db->select('wb_task_id');
                $this->db->from('work_board_task_hours');
                $this->db->where('wb_task_id', $holiday_wb_task->wb_task_id);
                $query = $this->db->get();
                
                if ($query->result()) {
                    $this->db->where('wb_task_id', $holiday_wb_task->wb_task_id);
                    $this->db->update('work_board_task_hours', $fields);
                } else {
                    $fields['wb_task_id'] = $holiday_wb_task->wb_task_id;
                    $this->db->insert('work_board_task_hours', $fields);
                }
                $rowsAffected++;
            }
        }
        return $rowsAffected;
    }
    
    private function emp_regular($c_id, $startdate)
    {
        $rowsAffected = 0;
        $sql = "
            SELECT workers.worker_id, SUM(working_session.end_time - working_session.start_time)/3600 AS est_hr, work_board.w_date, working_session.working_date,
            COALESCE(overtime_card.holiday_rate, 2) AS holiday_rate, work_board.workboard_hours, workers.wage, work_board.work_board_id
            FROM working_session
            INNER JOIN workers ON workers.worker_id = working_session.worker_id
            INNER JOIN work_board ON work_board.c_id = workers.c_id AND workers.d_id = work_board.d_id AND work_board.w_date = working_session.working_date
            LEFT OUTER JOIN overtime_card ON overtime_card.id = workers.overtime_card_id
            
            WHERE working_session.remove = 0 AND working_session.end_time > working_session.start_time AND work_board.c_id = " . $c_id . "
            AND (work_board.w_date BETWEEN '" . $startdate . "' AND DATE_ADD('" . $startdate . "', INTERVAL +6 DAY))
            AND NOT EXISTS (
                SELECT holidays.date
                FROM holidays
                WHERE holidays.c_id = work_board.c_id AND work_board.d_id = holidays.d_id AND holidays.date = SUBSTRING(work_board.w_date, 6)
            )
                
            GROUP BY workers.worker_id, working_session.working_date
            ORDER BY workers.worker_id, work_board.w_date
        ";
        $task_query = $this->db->query($sql);
        $tasks = $task_query->result_array();
        
        if ($tasks) {
            $e_tasks = array();
            foreach ($tasks as $task) {
                if (!isset($e_tasks[$task['worker_id']])) {
                    $e_tasks[$task['worker_id']] = array();
                }
                if (!isset($e_tasks[$task['worker_id']][$task['w_date']])) {
                    $e_tasks[$task['worker_id']][$task['w_date']] = array();
                }
                $e_tasks[$task['worker_id']][$task['w_date']][] = $task;
            }
            
            $week_days = array(1 => 'mon', 2 => 'tue', 3 => 'wed', 4 => 'thu', 5 => 'fri', 6 => 'sat', 7 => 'sun');
            
            foreach ($e_tasks as $worker_id => $days) {
                $ot_card = $this->get_overtime_card($worker_id);
                
                $rowtoupdate = array();
                $week_hrs = 0;
                $regular_hour_week = $ot_card['regular_hour_week'];
                foreach ($days as $day => $jobs) {
                    $day_of_week = date('N', strtotime($day));
                    $regular_hour = $ot_card['regular_hour_' . $week_days[$day_of_week]];
                    
                    $day_hrs = 0;
                    foreach ($jobs as $job) {
                        $job['ot_day_multiplier_new'] = $ot_card['overtime_rate_' . $week_days[$day_of_week]];
                        $job['ot_week_multiplier_new'] = $ot_card['overtime_rate_week'];
                        
                        if ($job['est_hr'] >= 0) { // if the est_hr is positive then do this
                            // OT by day
                            if (!empty($regular_hour) && $regular_hour >= 0 && !empty($job['ot_day_multiplier_new'])) {
                                $day_hrs += $job['est_hr'];
                                if ($day_hrs > $regular_hour) {
                                    if (($day_hrs - $regular_hour) > $job['est_hr']) {
                                        // over time greater than the job so apply all job hours to overtime
                                        $job['ot_day_hr_new'] = $job['est_hr'];
                                    } else {
                                        // only a portion on the hours fall into over time so only assign the portional hours;
                                        $job['ot_day_hr_new'] = $day_hrs - $regular_hour;
                                    }
                                } else {
                                    // no overtime found
                                    $job['ot_day_hr_new'] = 0;
                                }
                            }
                            
                            // OT by week
                            if ($regular_hour_week > 0 && $job['ot_week_multiplier_new'] > 0) {
                                $week_hrs += $job['est_hr'];
                                if ($week_hrs > $regular_hour_week) {
                                    if (($week_hrs - $regular_hour_week) > $job['est_hr']) {
                                        // over time greater than the job so apply all job hours to overtime
                                        $job['ot_week_hr_new'] = $job['est_hr'];
                                    } else {
                                        // only a portion on the hours fall into over time so only assign the portional hours;
                                        $job['ot_week_hr_new'] = $week_hrs - $regular_hour_week;
                                    }
                                } else {
                                    // no overtime found
                                    $job['ot_week_hr_new'] = 0;
                                }
                            }
                            
                        } else {// if the est_hr is negitive then do this
                            // OT by day
                            if (!empty($regular_hour) && $regular_hour >= 0 && !empty($job['ot_day_multiplier_new'])) {
                                if ($day_hrs > $regular_hour) {
                                    // overtime
                                    if (-($day_hrs - $regular_hour) < $job['est_hr']) {
                                        // over time greater than the job so apply all job hours to overtime
                                        $job['ot_day_hr_new'] = $job['est_hr'];
                                    } else {
                                        // only a portion on the hours fall into over time so only assign the portional hours;
                                        $job['ot_day_hr_new'] = -($day_hrs - $regular_hour);
                                    }
                                } else {
                                    $job['ot_day_hr_new'] = 0;
                                }
                                $day_hrs += $job['est_hr'];
                            }
                            
                            // OT by week
                            if ($regular_hour_week > 0 && $job['ot_week_multiplier_new'] > 0) {
                                if ($week_hrs > $regular_hour_week) {
                                    // overtime
                                    if (-($week_hrs - $regular_hour_week) < $job['est_hr']) {
                                        // over time greater than the job so apply all job hours to overtime
                                        $job['ot_week_hr_new'] = $job['est_hr'];
                                    } else {
                                        // only a portion on the hours fall into over time so only assign the portional hours;
                                        $job['ot_week_hr_new'] = -($week_hrs - $regular_hour_week);
                                    }
                                } else {
                                    $job['ot_week_hr_new'] = 0;
                                }
                                $week_hrs += $job['est_hr'];
                            }
                        }
                        $rowtoupdate[] = $job;
                    }
                }
                
                if ($rowtoupdate) {
                    foreach ($rowtoupdate as $row) {
                        $fields = array(
                            'worker_id' => $row['worker_id'],
                            'working_date' => $row['working_date'],
                            'hours_worked' => $row['est_hr'],
                            'holiday_hours' => 0,
                            'holiday_ot_hours' => 0,
                            'holiday_pay' => 0,
                            'holiday_ot_pay' => 0
                        );
                        if (isset($row['ot_day_hr_new']) && isset($row['ot_week_hr_new'])) {
                            if ($row['ot_day_hr_new'] >= $row['ot_week_hr_new'] && $row['ot_day_multiplier_new'] >= $row['ot_week_multiplier_new']) {
                                $ot_hours = $row['ot_day_hr_new'] ? $row['ot_day_hr_new'] : 0;
                                $regular_hours = $row['est_hr'] > $ot_hours ? ($row['est_hr'] - $ot_hours) : 0;
                                $fields['ot_hours'] = $ot_hours;
                                $fields['ot_pay'] = $row['ot_day_multiplier_new'] * $ot_hours * $row['wage'];
                                $fields['regular_hours'] = $regular_hours;
                                $fields['regular_pay'] = $regular_hours * $row['wage'];
                            } else if ($row['ot_day_hr_new'] <= $row['ot_week_hr_new'] && $row['ot_day_multiplier_new'] <= $row['ot_week_multiplier_new']) {
                                $ot_hours = $row['ot_week_hr_new'] ? $row['ot_week_hr_new'] : 0;
                                $regular_hours = $row['est_hr'] > $ot_hours ? ($row['est_hr'] - $ot_hours) : 0;
                                $fields['ot_hours'] = $ot_hours;
                                $fields['ot_pay'] = $row['ot_week_multiplier_new'] * $ot_hours * $row['wage'];
                                $fields['regular_hours'] = $regular_hours;
                                $fields['regular_pay'] = $regular_hours * $row['wage'];
                            } else if ($row['ot_day_hr_new'] >= $row['ot_week_hr_new'] && $row['ot_day_multiplier_new'] < $row['ot_week_multiplier_new']) {
                                $ot_hours = $row['ot_day_hr_new'] ? $row['ot_day_hr_new'] : 0;
                                $regular_hours = $row['est_hr'] > $ot_hours ? ($row['est_hr'] - $ot_hours) : 0;
                                $fields['ot_hours'] = $ot_hours;
                                $fields['ot_pay'] = $row['ot_day_multiplier_new'] * ($row['ot_day_hr_new'] - $row['ot_week_hr_new']) * $row['wage'];
                                $fields['ot_pay'] += $row['ot_week_multiplier_new'] * $row['ot_week_hr_new'] * $row['wage'];
                                $fields['regular_hours'] = $regular_hours;
                                $fields['regular_pay'] = $regular_hours * $row['wage'];
                            } else if ($row['ot_day_hr_new'] <= $row['ot_week_hr_new'] && $row['ot_day_multiplier_new'] > $row['ot_week_multiplier_new']) {
                                $ot_hours = $row['ot_week_hr_new'] ? $row['ot_week_hr_new'] : 0;
                                $regular_hours = $row['est_hr'] > $ot_hours ? ($row['est_hr'] - $ot_hours) : 0;
                                $fields['ot_hours'] = $ot_hours;
                                $fields['ot_pay'] = $row['ot_week_multiplier_new'] * ($row['ot_week_hr_new'] - $row['ot_day_hr_new']) * $row['wage'];
                                $fields['ot_pay'] += $row['ot_day_multiplier_new'] * $row['ot_day_hr_new'] * $row['wage'];
                                $fields['regular_hours'] = $regular_hours;
                                $fields['regular_pay'] = $regular_hours * $row['wage'];
                            }
                        } else if (isset($row['ot_day_hr_new'])) {
                            $ot_hours = $row['ot_day_hr_new'] ? $row['ot_day_hr_new'] : 0;
                            $regular_hours = $row['est_hr'] > $ot_hours ? ($row['est_hr'] - $ot_hours) : 0;
                            $fields['ot_hours'] = $ot_hours;
                            $fields['ot_pay'] = $row['ot_day_multiplier_new'] * $ot_hours * $row['wage'];
                            $fields['regular_hours'] = $regular_hours;
                            $fields['regular_pay'] = $regular_hours * $row['wage'];
                        } else if (isset($row['ot_week_hr_new'])) {
                            $ot_hours = $row['ot_week_hr_new'] ? $row['ot_week_hr_new'] : 0;
                            $regular_hours = $row['est_hr'] > $ot_hours ? ($row['est_hr'] - $ot_hours) : 0;
                            $fields['ot_hours'] = $ot_hours;
                            $fields['ot_pay'] = $row['ot_week_multiplier_new'] * $ot_hours * $row['wage'];
                            $fields['regular_hours'] = $regular_hours;
                            $fields['regular_pay'] = $regular_hours * $row['wage'];
                        }
                        
                        $this->db->select('id');
                        $this->db->from('working_session_hours');
                        $this->db->where('worker_id', $row['worker_id']);
                        $this->db->where('working_date', $row['working_date']);
                        $query = $this->db->get();
                        
                        if ($data = $query->result()) {
                            $data_item = $data[0];
                            
                            $this->db->where('id', $data_item->id);
                            $this->db->update('working_session_hours', $fields);
                        } else {
                            $this->db->insert('working_session_hours', $fields);
                        }
                        $rowsAffected++;
                    }
                }
            }
        }
        return $rowsAffected;
    }
    
    private function emp_holiday($c_id, $startdate)
    {
        $rowsAffected = 0;
        $sql = "
            SELECT workers.worker_id, SUM(working_session.end_time - working_session.start_time)/3600 AS act_hr, working_session.working_date,
            COALESCE(overtime_card.holiday_rate, 2) AS holiday_rate, work_board.workboard_hours, workers.wage, work_board.work_board_id
            FROM working_session
            INNER JOIN workers ON workers.worker_id = working_session.worker_id
            INNER JOIN work_board ON work_board.c_id = workers.c_id AND workers.d_id = work_board.d_id AND work_board.w_date = working_session.working_date
            LEFT OUTER JOIN overtime_card ON overtime_card.id = workers.overtime_card_id
            
            WHERE working_session.remove = 0 AND working_session.end_time > working_session.start_time
            AND (work_board.w_date BETWEEN '" . $startdate . "' AND DATE_ADD('" . $startdate . "', INTERVAL +6 DAY))
            AND work_board.c_id = " . $c_id . "
            AND EXISTS (
                SELECT holidays.date
                FROM holidays
                WHERE holidays.c_id = work_board.c_id AND work_board.d_id = holidays.d_id
                AND holidays.date = SUBSTRING(work_board.w_date, 6)
            )
                
            GROUP BY workers.worker_id, working_session.working_date
            ORDER BY workers.worker_id, work_board.w_date
        ";
        $holiday_query = $this->db->query($sql);
        $wss = $holiday_query->result();
        
        if ($wss) {
            foreach ($wss as $ws) {
                $holiday_hours = $ws->act_hr <= $ws->workboard_hours ? $ws->act_hr : $ws->workboard_hours;
                $holiday_ot_hours = $ws->act_hr > $ws->workboard_hours ? $ws->act_hr - $ws->workboard_hours : 0;
                $fields = array(
                    'worker_id' => $ws->worker_id,
                    'working_date' => $ws->working_date,
                    'hours_worked' => $ws->act_hr,
                    'regular_hours' => 0,
                    'ot_hours' => 0,
                    'holiday_hours' => $holiday_hours,
                    'holiday_ot_hours' => $holiday_ot_hours,
                    'regular_pay' => 0,
                    'ot_pay' => 0,
                    'holiday_pay' => $ws->wage * $ws->holiday_rate * $holiday_hours,
                    'holiday_ot_pay' => $ws->wage * $ws->holiday_rate * $holiday_ot_hours
                );
                
                
                $this->db->select('id');
                $this->db->from('working_session_hours');
                $this->db->where('worker_id', $ws->worker_id);
                $this->db->where('working_date', $ws->working_date);
                $query = $this->db->get();
                
                if ($data = $query->result()) {
                    $data_item = $data[0];
                    
                    $this->db->where('id', $data_item->id);
                    $this->db->update('working_session_hours', $fields);
                } else {
                    $this->db->insert('working_session_hours', $fields);
                }
                $rowsAffected++;
            }
        }
        return $rowsAffected;
    }
    
    private function get_overtime_card($worker_id)
    {
        $this->db->select('
            wage_type, holiday_rate, regular_hour_sun, regular_hour_mon, regular_hour_tue, regular_hour_wed,
            regular_hour_thu, regular_hour_fri, regular_hour_sat, regular_hour_week, overtime_rate_sun,
            overtime_rate_mon, overtime_rate_tue, overtime_rate_wed, overtime_rate_thu, overtime_rate_fri,
            overtime_rate_sat, overtime_rate_week
        ');
        $this->db->from('workers');
        $this->db->join('overtime_card', 'overtime_card.remove = 0 AND overtime_card.id = COALESCE(workers.overtime_card_id, 1)', 'LEFT OUTER JOIN');
        $this->db->where('workers.worker_id', $worker_id);
        $this->db->limit(1);
        
        $query = $this->db->get();
        $data = $query->result_array();
        
        if ($data) {
            return $data[0];
        } else {
            $this->db->select('
                wage_type, holiday_rate, regular_hour_sun, regular_hour_mon, regular_hour_tue, regular_hour_wed,
                regular_hour_thu, regular_hour_fri, regular_hour_sat, regular_hour_week, overtime_rate_sun,
                overtime_rate_mon, overtime_rate_tue, overtime_rate_wed, overtime_rate_thu, overtime_rate_fri,
                overtime_rate_sat, overtime_rate_week
            ');
            $this->db->from('overtime_card');
            $this->db->where('id', 1);
            $this->db->limit(1);
            
            $query = $this->db->get();
            $data = $query->result_array();
            
            if ($data) {
                return $data[0];
            }
        }
        
        return array(
            'wage_type' => 1,
            'holiday_rate' => 2,
            'regular_hour_sun' => 0,
            'regular_hour_mon' => 0,
            'regular_hour_tue' => 0,
            'regular_hour_wed' => 0,
            'regular_hour_thu' => 0,
            'regular_hour_fri' => 0,
            'regular_hour_sat' => 0,
            'regular_hour_week' => 40,
            'overtime_rate_sun' => 0,
            'overtime_rate_mon' => 0,
            'overtime_rate_tue' => 0,
            'overtime_rate_wed' => 0,
            'overtime_rate_thu' => 0,
            'overtime_rate_fri' => 0,
            'overtime_rate_sat' => 0,
            'overtime_rate_week' => 1.5
        );
    }
}