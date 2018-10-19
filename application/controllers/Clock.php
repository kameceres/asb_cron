<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Clock extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        
        $this->load->database();
    }

    /**
     * Load workboard
     */
    public function index()
    {
        set_time_limit(0);
        
        $this->db->select('
            mass_clock_in.id, mass_clock_in.work_board_id, mass_clock_in.worker_id, mass_clock_in.clock_in, mass_clock_in.start_task, 
            mass_clock_in.clock_out, work_board.w_date, mass_clock_in.break_start, mass_clock_in.break_end, mass_clock_in.status
        ');
        $this->db->from('mass_clock_in');
        $this->db->join('work_board', 'work_board.work_board_id = mass_clock_in.work_board_id', 'INNER');
        $this->db->where('(
            (mass_clock_in.status = 1 AND mass_clock_in.clock_in < ' . time() . ') OR 
            (mass_clock_in.status = 2 AND mass_clock_in.start_task < ' . time() . ') OR 
            (mass_clock_in.status = 3 AND mass_clock_in.break_start < ' . time() . ') OR 
            (mass_clock_in.status = 4 AND mass_clock_in.break_end < ' . time() . ') OR 
            (mass_clock_in.status = 5 AND mass_clock_in.clock_out < ' . time() . ')
        )');
        $query = $this->db->get();
        
        $data = $query->result();
        
        foreach ($data as $item) {
            $this->do_clock($item);
        }
        
        echo 'Successfull';
    }
    
    private function do_clock($item)
    {
        if ($item->status == 1 && $item->clock_in && $item->clock_in <= time()) {
            $this->db->select('working_session_id, start_time, end_time');
            $this->db->from('working_session');
            $this->db->where('worker_id', $item->worker_id);
            $this->db->where('remove', 0);
            $this->db->where('working_date', $item->w_date);
            $this->db->order_by('start_time');
            $query = $this->db->get();
            
            $working_sessions = $query->result();
            if ($working_sessions) {
                $first_ws = $working_sessions[0];
                
                if ($first_ws->start_time && !$first_ws->end_time && $first_ws->start_time < $item->clock_in) {
                    $this->db->set(['start_time' => $item->clock_in, 'start_time_input_type' => 2]);
                    $this->db->where('working_session_id', $first_ws->working_session_id);
                    $this->db->update('working_session');
                    
                    
                    $this->db->select('actual_time_keeping.time_id, actual_time_keeping.start_time, actual_time_keeping.end_time');
                    $this->db->from('actual_time_keeping');
                    $this->db->join('work_board_task', 'work_board_task.wb_task_id = actual_time_keeping.workboard_task_id', 'INNER');
                    $this->db->where('actual_time_keeping.worker_id', $item->worker_id);
                    $this->db->where('work_board_task.work_board_id', $item->work_board_id);
                    $this->db->where('actual_time_keeping.remove', 0);
                    $this->db->where('actual_time_keeping.start_time >= ' . $first_ws->start_time);
                    $this->db->limit(1);
                    $query = $this->db->get();
                    $time_keepings = $query->result();
                    
                    if ($time_keepings) {
                        $time_keeping = $time_keepings[0];
                        
                        $this->db->set(['start_time' => $item->clock_in, 'start_time_input_type' => 2]);
                        $this->db->where('time_id', $time_keeping->time_id);
                        $this->db->update('actual_time_keeping');
                    }
                    
                } else if ($first_ws->start_time && $first_ws->end_time && $first_ws->start_time < $item->clock_in && $first_ws->end_time >= $item->clock_in) {
                    $this->db->set(['start_time' => $item->clock_in, 'start_time_input_type' => 2]);
                    $this->db->where('working_session_id', $first_ws->working_session_id);
                    $this->db->update('working_session');
                    
                    $this->db->select('actual_time_keeping.time_id, actual_time_keeping.start_time, actual_time_keeping.end_time');
                    $this->db->from('actual_time_keeping');
                    $this->db->join('work_board_task', 'work_board_task.wb_task_id = actual_time_keeping.workboard_task_id', 'INNER');
                    $this->db->where('actual_time_keeping.worker_id', $item->worker_id);
                    $this->db->where('work_board_task.work_board_id', $item->work_board_id);
                    $this->db->where('actual_time_keeping.remove', 0);
                    $this->db->where('actual_time_keeping.start_time >= ' . $first_ws->start_time . ' AND actual_time_keeping.start_time <= ' . $first_ws->end_time);
                    $this->db->limit(1);
                    $query = $this->db->get();
                    $time_keepings = $query->result();
                    
                    if ($time_keepings) {
                        $time_keeping = $time_keepings[0];
                        
                        $this->db->set(['start_time' => $item->clock_in, 'start_time_input_type' => 2]);
                        $this->db->where('time_id', $time_keeping->time_id);
                        $this->db->update('actual_time_keeping');
                    }
                    
                } else {
                    $last_ws = $working_sessions[count($working_sessions) - 1];
                    
                    if ($last_ws->start_time && !$last_ws->end_time && $last_ws->start_time < $item->clock_in) {
                        $this->db->set(['start_time' => $item->clock_in, 'start_time_input_type' => 2]);
                        $this->db->where('working_session_id', $last_ws->working_session_id);
                        $this->db->update('working_session');
                        
                        $this->db->select('actual_time_keeping.time_id, actual_time_keeping.start_time, actual_time_keeping.end_time');
                        $this->db->from('actual_time_keeping');
                        $this->db->join('work_board_task', 'work_board_task.wb_task_id = actual_time_keeping.workboard_task_id', 'INNER');
                        $this->db->where('actual_time_keeping.worker_id', $item->worker_id);
                        $this->db->where('work_board_task.work_board_id', $item->work_board_id);
                        $this->db->where('actual_time_keeping.remove', 0);
                        $this->db->where('actual_time_keeping.start_time >= ' . $last_ws->start_time);
                        $this->db->limit(1);
                        $query = $this->db->get();
                        $time_keepings = $query->result();
                        
                        if ($time_keepings) {
                            $time_keeping = $time_keepings[0];
                            
                            $this->db->set(['start_time' => $item->clock_in, 'start_time_input_type' => 2]);
                            $this->db->where('time_id', $time_keeping->time_id);
                            $this->db->update('actual_time_keeping');
                        }
                        
                    } else if ($last_ws->start_time && $last_ws->end_time && $last_ws->end_time < $item->clock_in) {
                        $this->db->set([
                            'worker_id' => $item->worker_id,
                            'working_date' => $item->w_date,
                            'start_time' => $item->clock_in,
                            'start_time_input_type' => 2
                        ]);
                        $this->db->insert('working_session');
                        
                    } else if ($last_ws->start_time && $last_ws->end_time && $last_ws->end_time == $item->clock_in) {
                        $this->db->set(['end_time' => $item->clock_in - 1, 'end_time_input_type' => 2]);
                        $this->db->where('working_session_id', $last_ws->working_session_id);
                        $this->db->update('working_session');
                        
                        $this->db->select('actual_time_keeping.time_id, actual_time_keeping.start_time, actual_time_keeping.end_time');
                        $this->db->from('actual_time_keeping');
                        $this->db->join('work_board_task', 'work_board_task.wb_task_id = actual_time_keeping.workboard_task_id', 'INNER');
                        $this->db->where('actual_time_keeping.worker_id', $item->worker_id);
                        $this->db->where('work_board_task.work_board_id', $item->work_board_id);
                        $this->db->where('actual_time_keeping.remove', 0);
                        $this->db->where('actual_time_keeping.start_time >= ' . $last_ws->start_time . ' AND actual_time_keeping.start_time <= ' . $last_ws->end_time);
                        $this->db->limit(1);
                        $query = $this->db->get();
                        $time_keepings = $query->result();
                        
                        if ($time_keepings) {
                            $time_keeping = $time_keepings[0];
                            
                            if (!$time_keeping->end_time || $time_keeping->end_time == $item->clock_in) {
                                $this->db->set(['end_time' => $item->clock_in - 1, 'start_time_input_type' => 2]);
                                $this->db->where('time_id', $time_keeping->time_id);
                                $this->db->update('actual_time_keeping');
                            }
                        }
                        
                        $this->db->set([
                            'worker_id' => $item->worker_id,
                            'working_date' => $item->w_date,
                            'start_time' => $item->clock_in,
                            'start_time_input_type' => 2
                        ]);
                        $this->db->insert('working_session');
                    }
                }
                
            } else {
                $this->db->set([
                    'worker_id' => $item->worker_id,
                    'working_date' => $item->w_date,
                    'start_time' => $item->clock_in,
                    'start_time_input_type' => 2
                ]);
                $this->db->insert('working_session');
            }
            
            $this->db->set(['status' => 2]);
            $this->db->where('id', $item->id);
            $this->db->update('mass_clock_in');
        }
        
        if (($item->status == 1 || $item->status == 2) && $item->start_task && $item->start_task < time()) {
            $this->db->select('working_session_id');
            $this->db->from('working_session');
            $this->db->where('worker_id', $item->worker_id);
            $this->db->where('remove', 0);
            $this->db->where('working_date', $item->w_date);
            $this->db->where('start_time <= ' . $item->start_task);
            $this->db->where('(end_time IS NULL OR end_time = "")');
            $query = $this->db->get();
            
            $working_sessions = $query->result();
            if (!$working_sessions) {
                $this->db->set([
                    'worker_id' => $item->worker_id,
                    'working_date' => $item->w_date,
                    'start_time' => $item->clock_in ? $item->clock_in : $item->start_task,
                    'start_time_input_type' => 2
                ]);
                $this->db->insert('working_session');
            }
            
            $this->db->select('actual_time_keeping.time_id, actual_time_keeping.start_time, actual_time_keeping.end_time');
            $this->db->from('actual_time_keeping');
            $this->db->join('work_board_task', 'work_board_task.wb_task_id = actual_time_keeping.workboard_task_id', 'INNER');
            $this->db->where('actual_time_keeping.worker_id', $item->worker_id);
            $this->db->where('work_board_task.work_board_id', $item->work_board_id);
            $this->db->where('actual_time_keeping.remove', 0);
            $this->db->order_by('actual_time_keeping.start_time');
            $this->db->limit(1);
            $query = $this->db->get();
            
            $time_keepings = $query->result();
            
            if ($time_keepings) {
                $first_tk = $time_keepings[0];
                if ($first_tk->start_time && !$first_tk->end_time && $first_tk->start_time < $item->start_task) {
                    $this->db->set(['start_time' => $item->start_task, 'start_time_input_type' => 2]);
                    $this->db->where('time_id', $first_tk->time_id);
                    $this->db->update('actual_time_keeping');
                } else if ($first_tk->start_time && $first_tk->end_time && $first_tk->start_time < $item->start_task && $first_tk->end_time > $item->start_task) {
                    $this->db->set(['start_time' => $item->start_task, 'start_time_input_type' => 2]);
                    $this->db->where('time_id', $first_tk->time_id);
                    $this->db->update('actual_time_keeping');
                } else {
                    
                }
                
            } else {
                $this->db->select('work_board_task.wb_task_id');
                $this->db->from('work_board_task');
                $this->db->join('work_board', 'work_board_task.work_board_id = work_board.work_board_id', 'INNER');
                $this->db->where('work_board_task.worker_id', $item->worker_id);
                $this->db->where('work_board.work_board_id', $item->work_board_id);
                $this->db->order_by('sortorder');
                $this->db->limit(1);
                
                $query = $this->db->get();
                $tasks = $query->result();
                
                if ($tasks) {
                    $task = $tasks[0];
                    $this->db->set([
                        'worker_id' => $item->worker_id,
                        'workboard_task_id' => $task->wb_task_id,
                        'start_time' => $item->start_task,
                        'start_time_input_type' => 2
                    ]);
                    $this->db->insert('actual_time_keeping');
                }
            }
            
            $this->db->set(['status' => 3]);
            $this->db->where('id', $item->id);
            $this->db->update('mass_clock_in');
        }
        
        if ($item->status == 3) {
            if ($item->break_start && $item->break_start <= time()) {
                $this->db->select('actual_time_keeping.time_id, actual_time_keeping.start_time, actual_time_keeping.end_time');
                $this->db->from('actual_time_keeping');
                $this->db->join('work_board_task', 'work_board_task.wb_task_id = actual_time_keeping.workboard_task_id', 'INNER');
                $this->db->where('actual_time_keeping.worker_id', $item->worker_id);
                $this->db->where('work_board_task.work_board_id', $item->work_board_id);
                $this->db->where('actual_time_keeping.remove', 0);
                $this->db->where('actual_time_keeping.start_time <= ' . $item->break_start);
                $this->db->where('(actual_time_keeping.end_time IS NULL OR actual_time_keeping.end_time = "")');
                $query = $this->db->get();
                
                $time_keepings = $query->result();
                if ($time_keepings) {
                    foreach ($time_keepings as $time_keeping) {
                        $this->db->set(['end_time' => $item->break_start, 'end_time_input_type' => 2]);
                        $this->db->where('time_id', $time_keeping->time_id);
                        $this->db->update('actual_time_keeping');
                    }
                }
                
                $this->db->select('working_session_id');
                $this->db->from('working_session');
                $this->db->where('worker_id', $item->worker_id);
                $this->db->where('remove', 0);
                $this->db->where('working_date', $item->w_date);
                $this->db->where('start_time <= ' . $item->break_start);
                $this->db->where('(end_time IS NULL OR end_time = "")');
                $query = $this->db->get();
                
                $working_sessions = $query->result();
                
                if ($working_sessions) {
                    foreach ($working_sessions as $working_session) {
                        $this->db->set(['end_time' => $item->break_start, 'end_time_input_type' => 2]);
                        $this->db->where('working_session_id', $working_session->working_session_id);
                        $this->db->update('working_session');
                    }
                }
                
                $this->db->set(['status' => 4]);
                $this->db->where('id', $item->id);
                $this->db->update('mass_clock_in');
                
            } else if (!$item->break_start) {
                $this->db->set(['status' => 5]);
                $this->db->where('id', $item->id);
                $this->db->update('mass_clock_in');
            }
        }
        
        if ($item->status == 4) {
            if ($item->break_end && $item->break_end <= time()) {
                $this->db->select('working_session_id, start_time, end_time');
                $this->db->from('working_session');
                $this->db->where('worker_id', $item->worker_id);
                $this->db->where('remove', 0);
                $this->db->where('working_date', $item->w_date);
                $this->db->where('(end_time IS NULL OR end_time = "" OR end_time > ' . $item->break_end . ')');
                $query = $this->db->get();
                
                $working_sessions = $query->result();
                
                if (!$working_sessions) {
                    $this->db->set([
                        'worker_id' => $item->worker_id,
                        'working_date' => $item->w_date,
                        'start_time' => $item->break_end,
                        'start_time_input_type' => 2
                    ]);
                    $this->db->insert('working_session');
                    
                    
                    $this->db->select('actual_time_keeping.time_id, actual_time_keeping.workboard_task_id, actual_time_keeping.start_time, actual_time_keeping.end_time');
                    $this->db->from('actual_time_keeping');
                    $this->db->join('work_board_task', 'work_board_task.wb_task_id = actual_time_keeping.workboard_task_id', 'INNER');
                    $this->db->where('actual_time_keeping.worker_id', $item->worker_id);
                    $this->db->where('work_board_task.work_board_id', $item->work_board_id);
                    $this->db->where('actual_time_keeping.remove', 0);
                    $this->db->where('actual_time_keeping.end_time', $item->break_start);
                    $this->db->limit(1);
                    
                    $query = $this->db->get();
                    $time_keepings = $query->result();
                    if ($time_keepings) {
                        $time_keeping = $time_keepings[0];
                        
                        $this->db->select('actual_time_keeping.time_id, actual_time_keeping.workboard_task_id, actual_time_keeping.start_time, actual_time_keeping.end_time');
                        $this->db->from('actual_time_keeping');
                        $this->db->join('work_board_task', 'work_board_task.wb_task_id = actual_time_keeping.workboard_task_id', 'INNER');
                        $this->db->where('actual_time_keeping.worker_id', $item->worker_id);
                        $this->db->where('work_board_task.work_board_id', $item->work_board_id);
                        $this->db->where('actual_time_keeping.remove', 0);
                        $this->db->where('(end_time IS NULL OR end_time = "" OR end_time > ' . $item->break_end . ')');
                        $query = $this->db->get();
                        $overlap_time_keepings = $query->result();
                        
                        if (!$overlap_time_keepings) {
                            $this->db->set([
                                'worker_id' => $item->worker_id,
                                'workboard_task_id' => $time_keeping->workboard_task_id,
                                'start_time' => $item->break_end,
                                'start_time_input_type' => 2
                            ]);
                            $this->db->insert('actual_time_keeping');
                        }
                    }
                }
            }
            
            $this->db->set(['status' => 5]);
            $this->db->where('id', $item->id);
            $this->db->update('mass_clock_in');
        }
        
        if ($item->status == 5 && $item->clock_out && $item->clock_out <= time()) {
            $this->db->select('actual_time_keeping.time_id, actual_time_keeping.start_time, actual_time_keeping.end_time');
            $this->db->from('actual_time_keeping');
            $this->db->join('work_board_task', 'work_board_task.wb_task_id = actual_time_keeping.workboard_task_id', 'INNER');
            $this->db->where('actual_time_keeping.worker_id', $item->worker_id);
            $this->db->where('work_board_task.work_board_id', $item->work_board_id);
            $this->db->where('actual_time_keeping.remove', 0);
            $this->db->where('actual_time_keeping.start_time <= ' . $item->clock_out);
            $this->db->where('(actual_time_keeping.end_time IS NULL OR actual_time_keeping.end_time = "")');
            $query = $this->db->get();
            
            $time_keepings = $query->result();
            if ($time_keepings) {
                foreach ($time_keepings as $time_keeping) {
                    $this->db->set(['end_time' => $item->clock_out, 'end_time_input_type' => 2]);
                    $this->db->where('time_id', $time_keeping->time_id);
                    $this->db->update('actual_time_keeping');
                }
            }
            
            $this->db->select('working_session_id');
            $this->db->from('working_session');
            $this->db->where('worker_id', $item->worker_id);
            $this->db->where('remove', 0);
            $this->db->where('working_date', $item->w_date);
            $this->db->where('start_time <= ' . $item->clock_out);
            $this->db->where('(end_time IS NULL OR end_time = "")');
            $query = $this->db->get();
            
            $working_sessions = $query->result();
            
            if ($working_sessions) {
                foreach ($working_sessions as $working_session) {
                    $this->db->set(['end_time' => $item->clock_out, 'end_time_input_type' => 2]);
                    $this->db->where('working_session_id', $working_session->working_session_id);
                    $this->db->update('working_session');
                }
            }
            
            $this->db->set(['status' => 6]);
            $this->db->where('id', $item->id);
            $this->db->update('mass_clock_in');
        }
    }
    
    /**
     * Load workboard
     */
    public function schedule()
    {
        set_time_limit(0);
        
        date_default_timezone_set('America/Denver');
        
        $sql = '
            SELECT DISTINCT schedule_mass_clock_in.id, schedule_mass_clock_in.clock_in, schedule_mass_clock_in.start_first_task, schedule_mass_clock_in.clock_out, 
            schedule_mass_clock_in.break_start, schedule_mass_clock_in.break_end, schedule_mass_clock_in.status, companies.timezone, schedule_mass_clock_in.c_id
            FROM schedule_mass_clock_in
            INNER JOIN companies ON companies.company_id = schedule_mass_clock_in.c_id
            WHERE schedule_mass_clock_in.date <= "' . date('Y-m-d') . '" AND schedule_mass_clock_in.status = 1
            AND (schedule_mass_clock_in.c_id, schedule_mass_clock_in.date) 
            IN (SELECT c_id, MAX(date) FROM schedule_mass_clock_in WHERE status = 1 AND date <= "'. date('Y-m-d') . '" GROUP BY c_id)
        ';
        
        $query = $this->db->query($sql);
        $schedules = $query->result();
        
        if ($schedules) {
            foreach ($schedules as $schedule) {
                if (!$schedule->timezone) {
                    $schedule->timezone = 'America/Denver';
                }
                date_default_timezone_set($schedule->timezone);
                
                $this->db->distinct(true);
                $this->db->select('work_board.work_board_id, work_board_task.worker_id');
                $this->db->from('work_board');
                $this->db->join('work_board_task', 'work_board.work_board_id = work_board_task.work_board_id', 'INNER');
                $this->db->where('work_board.w_date', date('Y-m-d'));
                $this->db->where('work_board.c_id', $schedule->c_id);
                $this->db->where('NOT EXISTS (SELECT id FROM mass_clock_in WHERE worker_id = work_board_task.worker_id AND work_board_id = work_board.work_board_id)');
                $query = $this->db->get();
                
                $data = $query->result();
                
                if ($data) {
                    foreach ($data as $item) {
                        $this->db->insert('mass_clock_in', [
                            'work_board_id' => $item->work_board_id,
                            'worker_id' => $item->worker_id,
                            'clock_in' => strtotime(date('Y-m-d') . ' ' . $schedule->clock_in),
                            'start_task' => strtotime(date('Y-m-d') . ' ' . $schedule->start_first_task),
                            'clock_out' => strtotime(date('Y-m-d') . ' ' . $schedule->clock_out),
                            'break_start' => strtotime(date('Y-m-d') . ' ' . $schedule->break_start),
                            'break_end' => strtotime(date('Y-m-d') . ' ' . $schedule->break_end),
                            'status' => 1
                        ]);
                    }
                }
            }
        }
        
        echo 'Successfull';
    }
}