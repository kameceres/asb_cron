<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Workboard extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Load workboard
     */
    public function index()
    {
        $this->load->database();
        $showing_date = date("Y-m-d");
        $timezones = $this->getActiveTimezones($showing_date);
        $depts = $this->getDepartments($showing_date, $timezones);
        
        if ($depts) {
            foreach ($depts as $dept) {
                $wid = $this->getWorkboard($dept, $showing_date);
                $insert = true;
                
                if ($wid) {
                    $sb_ids = $this->getSplitBoards($wid);
                    
                    if ($sb_ids) {
                        foreach ($sb_ids as $sb_id) {
                            $emails = $this->getDepartmentEmails($dept->company_id, $dept->d_id);
                            if ($emails) {
                                $data = $this->getData($dept, $wid, $sb_id);
                                
                                if ($data) {
                                    $mowData = $this->getMowData($dept, $wid, $sb_id);
                                    $insert = $this->generatePdf($dept, $sb_id, $emails, $mowData, $data, $showing_date);
                                }
                            }
                        }
                    }
                }
                
                if ($insert) {
                    $this->db->insert('department_pdf', array(
                        'c_id' => $dept->company_id,
                        'd_id' => $dept->d_id,
                        'executed_date' => $showing_date
                    ));
                }
            }
        }
        echo 'Successfull';
    }
    
    private function updateConpanyTimezone()
    {
        $this->db->select('company_id, timezone');
        $this->db->from('companies');
        $this->db->where('remove', 0);
        
        $query = $this->db->get();
        $companies = $query->result();
        
        if ($companies) {
            foreach ($companies as $company) {
                $file = "../../memberdata/" . $company->company_id . "/info/presets.xml";
                
                if (file_exists($file)) {
                    $companyXML = simplexml_load_file($file);
                    $timezone = getPresetData($companyXML, 'default->weather->timezone');
                    $timezone = $timezone ? $timezone : 'America/Denver';
                    
                    if ($timezone != $company->timezone) {
                        $this->db->set('timezone', $timezone, FALSE);
                        $this->db->where('company_id', $company->company_id);
                        $this->db->update('companies');
                    }
                }
            }
        }
    }
    
    private function getActiveTimezones($showing_date)
    {
        $this->db->select('timezone');
        $this->db->distinct();
        $this->db->from('companies');
        
        $query = $this->db->get();
        $timezones = $query->result();
        
        $res = array();
        foreach ($timezones as $timezone) {
            $tz = $timezone->timezone ? $timezone->timezone : 'America/Denver';
            $date = new DateTime("now", new DateTimeZone($tz));
            if ($date->format('h') >= '01' && !in_array($tz, $res)) {
                $res[] = '"' . $tz . '"';
            }
        }
        return $res;
    }
    
    private function getDepartments($showing_date, $timezones)
    {
        $this->db->select('companies.company_id, companies.c_name, COALESCE(department.d_id, 1) AS d_id, department.department_name');
        $this->db->from('companies');
        $this->db->join('department', 'companies.company_id = department.c_id AND department.active = 1', 'LEFT OUTER');
        $this->db->where('companies.remove', 0);
        $this->db->where(
            '
            (
                companies.timezone IN (' . implode(',', $timezones) . ')
                AND NOT EXISTS (
                    SELECT id FROM department_pdf 
                    WHERE department_pdf.c_id = companies.company_id AND department_pdf.d_id = COALESCE(department.d_id, 1)
                    AND executed_date = "' . date('Y-m-d', strtotime($showing_date)) . '"
                )
            )
            ',
            null
        );
        $this->db->order_by('companies.company_id, COALESCE(department.d_id, 1)');
        
        $query = $this->db->get();
        $depts = $query->result();
        
        return $depts;
    }
    
    private function getDepartmentEmails($c_id, $d_id)
    {
        $this->db->select('email');
        $this->db->from('department_pdf_emails');
        $this->db->where('c_id', $c_id);
        $this->db->where('d_id', $d_id);
        $query = $this->db->get();
        $emails = $query->result();
        
        $res = '';
        if ($emails) {
            foreach ($emails as $email) {
                $res = $res . ($res ? ',' : '') . $email->email;
            }
        }
        return $res;
    }
    
    private function getWorkboard($dept, $showing_date)
    {
        $this->db->select('work_board_id');
        $this->db->from('work_board');
        $this->db->where('c_id', $dept->company_id);
        $this->db->where('d_id', $dept->d_id);
        $this->db->where('w_date', date('Y-m-d', strtotime($showing_date)));
        $this->db->limit(1);
        $query = $this->db->get();
        $workboards = $query->result();
        
        if ($workboards) {
            $workboard = $workboards[0];
            
            return $workboard->work_board_id;
        }
        return false;
    }
    
    private function getSplitBoards($wid)
    {
        $this->db->select('split_table_id');
        $this->db->from('split_table');
        $this->db->where('workboard_id', $wid);
        $query = $this->db->get();
        $sb = $query->result();
        
        if ($sb) {
            $res = array();
            foreach ($sb as $board) {
                $res[] = $board->split_table_id;
            }
            return $res;
        }
        
        return array(0);
    }
    
    private function generatePdf($dept, $sb_id, $emails, $mowData, $data, $showing_date)
    {
        $this->load->library('Pdf');
        
        $pdf = new Pdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetTitle('ASB ActiveBoard');
        $pdf->SetSubject('ASB ActiveBoard');
        $pdf->SetKeywords('TCPDF, PDF, ASB, ActiveBoard');
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetHeaderMargin(10);
        $pdf->SetTopMargin(10);
        $pdf->setFooterMargin(10);
        $pdf->SetAutoPageBreak(true);
        $pdf->setFontSubsetting(true);
        $pdf->SetFont('helvetica', '', 14, '', true);
        
        $pdf->AddPage();
        
        $html = $this->buildHtml($dept, $mowData, $data, $showing_date);
        
        $pdf->writeHTML($html, true, false, false, false, '');
        if (!is_dir(APPPATH . '../files/' . $dept->company_id)) {
            mkdir(APPPATH . '../files/' . $dept->company_id, 0777);
        }
        chmod(APPPATH . '../files/' . $dept->company_id, 0777);
        //$pdf->Output('activeboard.pdf', 'I');
        
        $pdf->Output(APPPATH . '../files/' . $dept->company_id . '/activeboard' . $dept->d_id . '_' . $sb_id . '.pdf', 'F');
        $b64Doc = chunk_split(base64_encode(file_get_contents(APPPATH . '../files/' . $dept->company_id . '/activeboard' . $dept->d_id . '_' . $sb_id . '.pdf')));
        
        $email = array();
        $email['from'] = 'tasktracker@asb.club';
        $email['to'] = $emails;
        $email['subject'] = "Activeboard";
        $email['html_body'] = "Please check attachment";
        $email['reply_to'] = 'jaime@advancedscoreboards.com';
        $email['attachments'] = array(
            array(
                'Name' => 'activeboard' . $dept->d_id . '_' . $sb_id . '.pdf',
                'ContentType' => 'application/octet-stream',
                'Content' => $b64Doc
            )
            
        );
        
        return $this->send_email($email);
    }
    
    private function send_email($email) {
        $json = json_encode(array(
            'From' => $email['from'],
            'To' => $email['to'],
            //'Cc' => $email['cc'],
            //'Bcc' => $email['bcc'],
            'Subject' => $email['subject'],
            //'Tag' => $email['tag'],
            'HtmlBody' => $email['html_body'],
            //'TextBody' => $email['text_body'],
            'ReplyTo' => $email['reply_to'],
            //'Headers' => $email['headers'],
            'Attachments' => $email['attachments']
        ));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://api.postmarkapp.com/email');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Postmark-Server-Token: '. POSTMARKKEY,
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $response = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $http_code === 200;
    }
    
    private function buildHtml($dept, $mowData, $workers, $showing_date)
    {
        $html = '
        <table style="font-size: 13px;" cellspacing="0" cellpadding="0">
    		<tbody>
        		<tr>
        			<td style="width: 50%; font-size: 14px; text-align: left; border-bottom: 1px solid #dedede;">
        				<div style="">' . (isset($dept->department_name) ? $dept->department_name : $dept->c_name) . '</div>
        			</td>
        			<td style="width: 50%; font-size: 14px; text-align: right; border-bottom: 1px solid #dedede;">
        				<div style="">' . date('m/d/Y', strtotime($showing_date)) . '</div>
        			</td>
                </tr>
        		<tr>
        			<td style="width: 100%; font-size: 14px; text-align: left; border-bottom: 1px solid #dedede;">';
        
        

        if ($mowData) {
            $html .= '
        				<table>
        				    <tr><td colspan="4"></td></tr>
        				    <tr>';
        
            foreach ($mowData as $mow) {
                if (!$mow->clock_ring_path) {
                    continue;
                }
                $title = $mow->mp_cat == 1 ? 'Greens' : ($mow->mp_cat == 2 ? 'Fairways' : ($mow->mp_cat == 3 ? 'Tees' : 'Approaches'));
                $html .= '<td style="width: 25%; text-align: center; font-size: 12px;">' . $title . '</td>';
            }
        
	       $html .= '
        				    </tr>
    				    </table>';
        
        }
        
	    $html .= '
        			</td>
                </tr>
			    <tr><td style="width: 100%;"></td></tr>';
	    
	    $index = 1;
	    foreach ($workers as $worker) {
	        if ($index % 3 == 1) {
	            if ($index > 1) {
	                $html .= '</tr>';
	                $html .= '<tr><td style="width: 100%;"></td></tr>';
	            }
	            $html .= '<tr>';
	        }
	        $tasks = $worker['task'];
	        $html .= '
    	        <td style="width: 33%; font-size: 12px; text-align: left;">
        	        <table style="border: 1px solid #dedede; padding: 0;">
            	        <tr><td style="border-bottom: 1px solid #dedede; background-color: #' . $worker['g_color'] . ';">' . $worker['fn'] . ' ' . $worker['ln'] . '</td></tr>';
	        
            foreach ($tasks as $task) {
                $html .= '<tr><td>' . $task['j_title'] . ($task['j_hr'] ? (' (' . $task['j_hr'] . ')') : '') . ($task['j_n'] ? ' - ' . $task['j_n'] : '') . '</td></tr>';
            }
            
	        $html .= '
        	        </table>
    	        </td>
            ';
	        $index ++;
	    }
	    $html .= '</tr>';
	    
	    $html .= '
            </tbody>
        </table>
        ';
        
        
        return $html;
    }
    
    private function getMowData($dept, $wid, $sb_id)
    {
        $this->db->select(
            'mow_pattern_options.mp_cat, workboard_mp.rotation, mow_pattern_options.clock_face_path,
            COALESCE(company_mow_patterns.new_clock_face_path, mow_pattern_options.clock_ring_path) AS clock_ring_path,
            mow_pattern_options.img_path, mow_rotations.img_path AS rotation_img_path, workboard_mp.step_cut'
        );
        $this->db->from('workboard_mp');
        $this->db->join('work_board', 'work_board.work_board_id = workboard_mp.workboard_id', 'INNER');
        $this->db->join('mow_pattern_options', 'mow_pattern_options.mow_pattern_id = workboard_mp.mow_pattern_id AND mow_pattern_options.remove = 0', 'INNER');
        $this->db->join('mow_rotations', 'mow_rotations.mow_rotation_id = workboard_mp.rotation', 'LEFT OUTER');
        $this->db->join(
            'company_mow_patterns',
            'company_mow_patterns.mow_pattern_id = mow_pattern_options.mow_pattern_id AND company_mow_patterns.is_use = 1
            AND company_mow_patterns.c_id = ' . $dept->company_id . ' AND company_mow_patterns.d_id = ' . $dept->d_id,
            'LEFT OUTER'
        );
        $this->db->where('workboard_mp.workboard_id', $wid);
        $this->db->where('workboard_mp.sb_id', $sb_id);
        $this->db->order_by('mow_pattern_options.mp_cat, mow_pattern_options.sort_order');
        
        $query = $this->db->get();
        return $query->result();
    }
    
    private function getData($dept, $wid, $sb_id)
    {
        $this->db->select(
            'workers.group_id, groups.group_color, workers.last_name, workers.first_name,
            work_board_task.worker_id, work_board_task.task_id, tasks.task_name,
            work_board_task.true_est_hr, work_board_task.task_notes,
            workers_departments.sort_display_id, work_board_task.wb_task_id, workers.worker_img,
            work_board_task.wb_task_id, work_board_task.est_hr, work_board_task.est_act, 
            workboard_task_notes.notes, workers_departments.department_id, workers.lang_code,
            tasks.core, task_colors.color AS task_color, task_colors.sort AS task_color_order'
        );
        $this->db->from('work_board_task');
        $this->db->join('workers', 'work_board_task.worker_id = workers.worker_id', 'INNER');
        $this->db->join('workers_departments', 'workers.worker_id = workers_departments.worker_id', 'INNER');
        $this->db->join('work_board', 'work_board.work_board_id = work_board_task.work_board_id AND workers_departments.department_id = work_board.d_id', 'INNER');
        $this->db->join('tasks', 'work_board_task.task_id = tasks.task_id', 'LEFT OUTER');
        $this->db->join('task_colors', 'task_colors.id = tasks.task_color_id', 'LEFT OUTER');
        $this->db->join('groups', 'groups.group_id = workers_departments.g_id', 'LEFT OUTER');
        $this->db->join('workboard_task_notes', 'workboard_task_notes.task_id = tasks.task_id AND workboard_task_notes.job_index = work_board_task.sortorder AND workboard_task_notes.work_board_id = work_board_task.work_board_id', 'LEFT OUTER');
        
        $this->db->where('work_board.work_board_id', $wid);
        $this->db->where('work_board_task.sb_id', $sb_id);
        $this->db->where('work_board_task.task_id >= 0');
        $this->db->order_by('workers_departments.department_id, work_board_task.sortorder, work_board_task.wb_task_id, workers.last_name, workers.first_name');

        $query = $this->db->get();
        $data = $query->result();
        
        $res = array();
        if ($data) {
            foreach ($data as $item) {
                 if (!isset($res[$item->worker_id])) {
                     $res[$item->worker_id] = array(
                         'g_color' => $item->group_color,
                         'fn' => $item->first_name,
                         'ln' => $item->last_name,
                         'task' => array()
                     );
                 }
                 
                 $res[$item->worker_id]['task'][] = array(
                     'j_title' => $item->task_name,
                     'j_hr' => $item->true_est_hr,
                     'j_n' => $item->notes . ($item->notes && $item->task_notes ? ' - ' : '') . $item->task_notes
                 );
            }
        }
        
        return $res;
    }
}