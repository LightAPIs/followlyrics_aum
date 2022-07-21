<?php
require ('simpleHtmlDom.php');

class AumFollowHandler {
    public static $siteSearch = 'https://zh.followlyrics.com/search?type=lyrics&name=';
    public static $siteDownload = 'https://zh.followlyrics.com/lyrics/';
    public static $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.5005.63 Safari/537.36';

    public static function getContent($url) {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($curl, CURLOPT_USERAGENT, self::$userAgent);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($curl);
        curl_close($curl);

        return $result === false ? '' : $result;
    }

    public static function search($title, $artist) {
        $results = array();
        $url = self::$siteSearch . urlencode($title);
        $html = new simple_html_dom();
        $html->load(self::getContent($url));
        $items = $html->find('.table-striped tbody tr');
        foreach($items as $ele) {
            $song = '';
            $id = '';
            $des = '';
            $singers = array();

            $links = $ele->find('td a');
            foreach($links as $li) {
                $href = $li->href;
                $text = trim($li->text());
                if (strpos($href, '/lyrics/') !== false) {
                    if ($text !== '' && $text !== '歌词') {
                        $song = $text;
                        $id = self::getIdFromSrc($href);
                    }
                } elseif (strpos($href, '/artist/') !== false) {
                    array_push($singers, $text);
                } elseif (strpos($href, '/album/') !== false) {
                    $des = $text;
                }
            }

            if ($song !== '' && $id !== '' && count($singers) > 0) {
                array_push($results, array('song' => $song, 'id' => $id, 'singers' => $singers, 'des' => $des));
            }
        }
        $html->clear();

        return $results;
    }

    public static function downloadLyric($songId) {
        $res = '';
        $downloadLrcUrl = self::$siteDownload . $songId . '/lrc';
        $res = self::getContent($downloadLrcUrl);
        if ($res === '') {
            $downloadTxtUrl = self::$siteDownload . $songId . '/txt';
            $res = self::getContent($downloadTxtUrl);
        }
        return $res;
    }

    public static function getIdFromSrc($src) {
        if (preg_match('/\/lyrics\/\d+/i', $src)) {
            preg_match('/\/lyrics\/(\d+)/i', $src, $matches);
            return $matches[1];
        }
        return '';
    }
}
