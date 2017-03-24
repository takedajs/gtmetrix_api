<?php

class Test extends CI_Model
{
    function __construct() {
        parent::__construct();

        $this->load->database();
    }

    /**
     * @return mixed
     */
    function getSites()
    {
        $sql = '
            SELECT
                id
                , name
            FROM sites
            WHERE
                display_flag = 1
            ORDER BY
                display_order
        ';

        $query = $this->db->query($sql);

        // 結果セットを連想配列として返す
        return $query->result_array();

    }

    /**
     * @param $site_id
     * @return mixed
     */
    function getSiteUrls($site_id = false)
    {
        $where = '';
        $params = array();
        if ($site_id) {
            $where = ' AND site_id = ? ';
            $params[] = $site_id;
        }

        $sql = "
            SELECT
                id
                , site_id
                , url
                , reference_speed
                , ad_flag
            FROM site_urls
            WHERE
                display_flag = 1
            {$where}
            ORDER BY
                display_order
        ";

        $query = $this->db->query($sql, $params);

        // 結果セットを連想配列として返す
        return $query->result_array();
    }

    function getTestResults($params)
    {
        $test_date_next_day = date('Y-m-d', strtotime('+1 day', strtotime($params['test_date'])));

        $sql = "
            SELECT
                site_url_id
                ,AVG(page_speed) as page_speed_average
            FROM test_results
            WHERE
                site_url_id = ? AND
                test_date >= ? AND
                test_date < ? AND
                page_speed < ?
            ";

        // 基準値の3倍 = 外れ値 todo:timesの数をconfingで管理する
        $times = 3;

        $conditions = array(
            $params['site_url_id'],
            $params['test_date'],
            $test_date_next_day,
            $params['reference_speed']*$times
        );

        $query = $this->db->query($sql, $conditions);
        
        // 結果セットを連想配列として返す
        return $query->result_array();
    }

    /**
     * 複数の計測結果を一括で格納する
     */
    function insertTestResult($data)
    {
        // todo:格納失敗したときに、例外処理でキャッチできないような気がする・・・
        $this->db->insert_batch('test_results', $data);
    }

    /**
     * 複数の計測結果平均スピードを一括で格納する
     */
    function insertTestResultAverages($data)
    {
        // todo:格納失敗したときに、例外処理でキャッチできないような気がする・・・
        $this->db->insert_batch('result_averages', $data);
    }

}

