<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Gtmetrix_test_average extends CI_Controller {

    public function index($date = false)
    {
        try {
            // ブラウザからのアクセスを制限
//            if (!is_cli()) {
//                set_status_header(403);
//                echo "cliから実行しなければいけない";
//                return;
//            }

            // 指定した日(引数)の計測結果を取得する。
            // 引数がない場合は、前日の計測結果の平均を出す。
            if (strptime($date, '%Y-%m-%d')) {
                $test_date = $date;
            } else {
                $test_date = date('Y-m-d', strtotime('-1 day'));
            }

            $this->load->model('test');
            $siteUrls = $this->test->getSiteUrls();

            $result_averages_datas = "";
            foreach ($siteUrls as $siteUrl) {

                $result_average_params = array(
                    'site_url_id'       => $siteUrl['id'],
                    'reference_speed'   => $siteUrl['reference_speed'],
                    'test_date'         => $test_date
                );

                $result_test_results = $this->test->getTestResults($result_average_params);
                
                if (!empty($result_test_results[0]['site_url_id'])) {
                    $result_averages_datas[] = array(
                        'site_url_id'           => $result_test_results[0]['site_url_id'],
                        'page_speed_average'    => $result_test_results[0]['page_speed_average'],
                        "calculation_date"      => date("Y-m-d", strtotime($test_date))
                    );
                }
            }

            if ($result_averages_datas != "") {
                $this->db->trans_start();

                $this->test->insertTestResultAverages($result_averages_datas);

                $this->db->trans_complete();
            }

        } catch (Exception $e) {
            $this->db->trans_rollback();
            // todo:エラーをメールで送信する
            var_dump($e->getMessage());
        }
        // todo:成功をメールで送信する
        var_dump('正常終了');
    }
}
