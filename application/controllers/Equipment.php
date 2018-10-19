<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Equipment extends MY_Controller
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
        
        $this->db->distinct();
        $this->db->select('c_id');
        $this->db->from('fav_equipment_cron');
        $this->db->limit(3);
        
        $query = $this->db->get();
        $companies = $query->result();
        
        if ($companies) {
            foreach ($companies as $company) {
                $sql = '
                    UPDATE equipment_assignment
                    INNER JOIN (
                    SELECT work_board_task.worker_id, work_board_task.task_id, work_board_task_equipments.equipment_id,
                    COUNT(DISTINCT work_board_task_equipments.id) AS used_time
                    
                    FROM work_board_task_equipments
                    INNER JOIN work_board_task ON work_board_task.wb_task_id = work_board_task_equipments.wb_task_id
                    INNER JOIN work_board ON work_board.work_board_id = work_board_task.work_board_id
                    INNER JOIN equipment_assignment ON work_board_task.worker_id = equipment_assignment.worker_id
                    AND work_board_task.task_id = equipment_assignment.task_id
                    AND work_board_task_equipments.equipment_id = equipment_assignment.equipment_id
                    
                    WHERE work_board.c_id = ' . $company->c_id . '
                    AND ((equipment_assignment.start_date IS NULL AND work_board.w_date >= "' . date('Y-m-d', strtotime ("-1 year", time())) . '")
                    OR (equipment_assignment.start_date IS NOT NULL AND work_board.w_date >= equipment_assignment.start_date))
                    AND work_board.w_date <= "' . date('Y-m-d') . '"
                    GROUP BY work_board_task.worker_id, work_board_task.task_id, work_board_task_equipments.equipment_id
                    ) eq_assigned ON eq_assigned.worker_id = equipment_assignment.worker_id
                    AND eq_assigned.task_id = equipment_assignment.task_id
                    AND eq_assigned.equipment_id = equipment_assignment.equipment_id
                    SET equipment_assignment.used_time = eq_assigned.used_time
                ';
                
                $this->db->query($sql);
                
                $sql1 = '
                    INSERT INTO equipment_assignment (worker_id, task_id, equipment_id, used_time)
                    SELECT work_board_task.worker_id, work_board_task.task_id, work_board_task_equipments.equipment_id,
                    COUNT(DISTINCT work_board_task_equipments.id) AS used_time
                    FROM work_board_task_equipments
                    INNER JOIN work_board_task ON work_board_task.wb_task_id = work_board_task_equipments.wb_task_id
                    INNER JOIN work_board ON work_board.work_board_id = work_board_task.work_board_id
                    WHERE work_board.c_id = ' . $company->c_id . '
                    AND work_board.w_date >= "' . date('Y-m-d', strtotime ("-1 year", time())) . '" AND work_board.w_date <= "' . date('Y-m-d') . '"
                    AND NOT EXISTS (SELECT * FROM equipment_assignment WHERE worker_id = work_board_task.worker_id
                    AND task_id = work_board_task.task_id AND equipment_id = work_board_task_equipments.equipment_id)
                    GROUP BY work_board_task.worker_id, work_board_task.task_id, work_board_task_equipments.equipment_id
                ';
                
                $this->db->query($sql1);
                
                $this->db->where('c_id', $company->c_id);
                $this->db->delete('fav_equipment_cron');
            }
        }
        
        
        echo 'Successfull';
    }
}