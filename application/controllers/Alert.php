<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Alert extends MY_Controller
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
        
        $companies = $this->pullInactiveCompanies();
        
        if ($companies) {
            $html = $this->renderTable($companies);
            
            $email = array();
            $email['from'] = 'tasktracker@asb.club';
            $email['to'] = 'jaime@advancedscoreboards.com, gerald@advancedscoreboards.com, tran.pham@ceresolutions.com';
            //$email['to'] = 'tran.pham@ceresolutions.com';
            $email['reply_to'] = 'jaime@advancedscoreboards.com';
            $email['subject'] = "Inactive Users";
            $email['html_body'] = $html;
            
            $this->send_email($email);
        }
        
        echo 'Successfull';
    }
    
    private function pullInactiveCompanies()
    {
        $tstamp = time();
        $this->db->select('
            companies.company_id, companies.c_name, companies.timezone, companies.remove, companies.c_phone_number,
            companies.c_address_1, companies.c_city, companies.c_state, companies.c_zip,
            department.d_id, department.department_name, department.alert_date, wb.inactive_days, department.alert_enable,
            users.id, users.first_name, users.last_name, users.phone_number
        ');
        $this->db->from('companies');
        $this->db->join('company_apps', 'companies.company_id = company_apps.c_id AND company_apps.app_id = 3 AND expire_date >= "' . date('Y-m-d') . '"', 'INNER OUTER');
        $this->db->join('department', 'companies.company_id = department.c_id', 'LEFT OUTER');
        $this->db->join('users', 'users.c_id = companies.company_id AND use_as_contact = 1 AND users.remove = 0', 'LEFT OUTER');
        $this->db->join('
        (
            SELECT c_id, d_id, MIN(CEIL((' . $tstamp . ' - tstamp)/86400)) AS inactive_days
            FROM work_board
            GROUP BY c_id, d_id
        ) wb', 'wb.c_id = companies.company_id AND wb.d_id = department.d_id', 'LEFT OUTER');
        $this->db->where('department.active', 1);
        $this->db->where('EXISTS
        (
            SELECT c_id, d_id
            FROM work_board
            WHERE c_id = companies.company_id
            GROUP BY c_id, d_id
            HAVING MIN(CEIL((' . $tstamp . ' - tstamp)/86400)) > 7
        )', null);
        $this->db->order_by('companies.timezone, companies.c_name, department.department_name');
        
        $query = $this->db->get();
        $data = $query->result();
        
        $companies = array();
        // see if a row was returned
        if ($data) {
            foreach ($data as $item) {
                if (!isset($companies[$item->company_id])) {
                    $companies[$item->company_id] = array(
                        'c_name' => $item->c_name,
                        'c_phone_number' => $item->c_phone_number,
                        'c_address_1' => $item->c_address_1,
                        'c_city' => $item->c_city,
                        'c_zip' => $item->c_zip,
                        'c_state' => $item->c_state,
                        'timezone' => $item->timezone,
                        'alert_date' => $item->alert_date,
                        'depts' => array(),
                        'contacts' => array()
                    );
                }
        
                $companies[$item->company_id]['depts'][$item->d_id] = array(
                    'd_id' => $item->d_id,
                    'department_name' => $item->department_name,
                    'inactive_days' => $item->inactive_days,
                    'alert_enable' => $item->alert_enable
                );
        
                $companies[$item->company_id]['contacts'][$item->id] = array(
                    'id' => $item->id,
                    'first_name' => $item->first_name,
                    'last_name' => $item->last_name,
                    'phone_number' => $item->phone_number
                );
            }
        }
        
        return $companies;
    }
    
    private function renderTable($companies)
    {
        $html = '
            <table class="inactive-table" border="1">
            <thead class="inactive-table-header">
                <tr>
                    <th class="inactive-header-item table-course">Course Name</th>
                    <th class="inactive-header-item table-department">Department</th>
                    <th class="inactive-header-item table-inactive">Inactive</th>
                    <th class="inactive-header-item table-contact">Contact Name(s)</th>
                    <th class="inactive-header-item table-phone">Phone #</th>
                    <th class="inactive-header-item table-state">State</th>
                    <th class="inactive-header-item table-timezone">Timezone</th>
                    <th class="inactive-header-item table-open"></th>
                </tr>
            </thead>
            <tbody class="inactive-table-body">
        ';
        foreach ($companies as $c_id => $company) {
            $html .= '
                <tr>
                    <td class="inactive-body-item table-course">' . $company['c_name'] . '</td>
                    <td class="inactive-body-item table-department">';
                foreach ($company['depts'] as $dept) {
                    $html .= '<p class="ina-department-item ' . ($dept['inactive_days'] > 7 ? 'high-alert' : 'low-alert') . '">' . $dept['department_name'] . '</p>';
                }
            
                $html .= '
                    </td>
                    <td class="inactive-body-item table-inactive">';
            
                foreach ($company['depts'] as $dept) {
                    $html .= '<p class="ina-inactive-item ' . ($dept['inactive_days'] > 7 ? 'high-alert' : 'low-alert') . '">' . $dept['inactive_days'] . '</p>';
                }
                $html .= '
                    </td>
                    <td class="inactive-body-item table-contact">';
            
                foreach ($company['contacts'] as $contact) {
                    $html .= $contact['first_name'] . ' ' . $contact['last_name'] . '<br/>';
                }
                $html .= '
        			</td>
                    <td class="inactive-body-item table-phone">' . $company['c_phone_number'] . '</td>
                    <td class="inactive-body-item table-state">' . $company['c_state'] . '</td>
                    <td class="inactive-body-item table-timezone">' . $company['timezone'] . '</td>
                    <td class="inactive-body-item table-open">
                        <a class="open-inactive-modal" href="#" c_id="' . $c_id . '"><i class="fa fa-inbox"></i></a>
                    </td>
                </tr>
            ';
        }
        
        $html .= '
            </tbody>
            </table>
        ';
        return $html;
    }
}