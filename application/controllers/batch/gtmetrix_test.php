<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Gtmetrix_test extends CI_Controller {

    public function index()
    {

        try {
            // ブラウザからのアクセスを制限
//            if (!is_cbli()) {
//                set_status_header(403);
//                echo "cliから実行しなければいけない";
//                return;
//            }

            $this->load->model('test');
            $sites = $this->test->getSites();

            foreach ($sites as $site) {
                $siteUrls = $this->test->getSiteUrls($site['id']);
                foreach ($siteUrls as $siteUrl) {

                    $test_id = $this->_getTestId($siteUrl);

                    $test_datas[] = array(
                        'site_url_id'   => $siteUrl['id'],
                        'test_id'       => $test_id,
                        "test_date"     => date("Y-m-d h:i:s")
                    );
                }
            }

//            var_dump($test_datas);exit;

//          ページを解析後、30秒以上立たないと計測結果が取得できない。
//          念のため、60sスリープさせる。
            sleep(30);

            // ページ速度を取得して要素に追加する
            foreach ($test_datas as &$test_data) {
                $test_result = $this->_getTestResult($test_data["test_id"]);

                $test_data["page_speed"] = $test_result["page_load_time"];
            }

            $this->db->trans_start();

            $this->test->insertTestResult($test_datas);

            $this->db->trans_complete();

        } catch (Exception $e) {
            $this->db->trans_rollback();
            // todo:エラーをメールで送信する
//            var_dump($e->getMessage());
        }
        // todo:成功をメールで送信する
//        var_dump('正常終了');
    }

    /**
     * 計測対象ページを解析して、計測IDを返す
     * @param $siteUrl
     * @return mixed
     */
    private function _getTestId ($siteUrl)
    {
//      ページ解析するとクレジットを消費してしまうので、テスト時にコメントアウトする
        $datas = array(
            'url' => $siteUrl['url'],
            'x-metrix-adblock' => $siteUrl['ad_flag']
        );

        $ch = curl_init();
        //ベーシック認証
        curl_setopt($ch, CURLOPT_USERPWD, $this->_getEmailApiKey());
        curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, config_item('gtmetrix_test_url'));
        $result = curl_exec($ch);
        curl_close($ch);

        $json_decode_result = json_decode($result, true);

        return $json_decode_result["test_id"];
//        return "xsxz8BJO";
    }

    /**
     * 計測結果を取得して返す
     * @param $test_id
     * @return mixed
     */
    private function _getTestResult ($test_id)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERPWD, $this->_getEmailApiKey());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, config_item('gtmetrix_test_url')."/".$test_id);
        $result = curl_exec($ch);
        curl_close($ch);

        $json_decode_result = json_decode($result, true);
        
        return $json_decode_result["results"];
    }

    /**
     * GTmetrixAPI説持続情報を返す
     * @return string
     */
    private function _getEmailApiKey()
    {
        return config_item('gtmetrix_email') . ':' . config_item('gtmetrix_api_key');
    }
}
