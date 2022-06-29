<?php
ini_set('display_errors', "On");
// 文字コード設定
header('Content-Type: application/json; charset=UTF-8');

// リクエストした URL
$request_uri = $_SERVER["REQUEST_URI"];

// ここのURL
$url = "/emailCheck/";
// $url = "/emailCheck/";

// フルURL
$full_url = "https://api.itsystem-lab.com".$url;
// $full_url = "http://localhost".$url;

// メールアドレスの解析
$email = substr($request_uri, mb_strlen($url));

/*
    構文の確認
 */

// エラー収納用
$error = [];

// もし、Emailが入力されていないなら 
if($email == null) array_push($error, "Enter your Email.");

// もし、Emailの文字数が256文字を超えていたら
if( mb_strlen($email) > 256 ) array_push($error, "The Email must be no longer than 256 characters.");

// もし、Emailの文字数が7文字を以下なら
if( mb_strlen($email) < 7 ) array_push($error, "The Email must be at least 7 characters long.");

// 初期値設定
$at = 0; // @(アットマーク)の数を数える用

// emailの文字列を一つずつ繰り返す。 例 [sample@sample.com]の場合、"s"»"a"»"m"»...»"o"»"m"
for ($i=0; $i < mb_strlen($email); $i++) { 

    // 文字列を取得
    $str = substr($email, $i, 1);

    // もし、最初の文字なら
    if( $i == 0 ){

        // もし、英数字ではないなら
        if( !ctype_alnum( $str ) ) array_push($error, "The first character must be alphanumeric.");
    }
    
    // もし、アットマークなら
    if( $str =="@" ){

        // $atの数を一つ増やす。
        $at++;
    }

    // もし、最後の文字なら
    if( $i == mb_strlen($email) ){

        // もし、英数字ではないなら
        if( !ctype_alnum( $str ) ) array_push($error, "The last character must be alphanumeric.");
    
    }

    // もし、英数字以外で
    if( !ctype_alnum( $str ) ){

        // 表示用のn文字目
        $n = $i + 1;

        // 使える文字列以外を使っている
        if( $str != "@" && $str != "_" && $str != "-" && $str != "." ) array_push($error, "An invalid character is used for the $n character. [ $str ]");
    }
}

// もし、@(アットマーク)の数が、1つではないなら
if( $at != 1 ) array_push($error, "Only one \"@\" must be placed.");

// @(アットマーク)の直前に[.](ドットマーク)があるなら
if( strpos($email, ".@") ) array_push($error, "Put \".\" just before \"@\".");

// @(アットマーク)の直後に[.](ドットマーク)があるなら
if( strpos($email, "@.") ) array_push($error, "Put \".\" just before \"@\".");

/*
    ドメインの確認
 */

// ログ用
$log = [];

// もし、今のところエラーがないなら
if( count($error) == 0 ){

    // 構文をtrueにする
    $syntax = true;

    // @(アットマーク)で分割する
    $division = explode("@", $email);

    // ユーザーを抽出
    $user = $division[0];

    // ドメインを抽出
    $domain = $division[1];

    /*
        APIを利用してドメイン情報を取得する
    */

    // URLを生成
    $url = "https://api.itsystem-lab.com/dns/$domain";

    // JSONデータで取得
    if( $json = @file_get_contents($url) ){

        // 文字化けしないようにする
        $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');

        // JSONデータを連想配列の形式にする
        $json = json_decode($json, true);

        // もし、MXレコードが存在しないなら
        if( empty($json["MX"]) || count($json["MX"]) == 0 ) array_push($error, "Failed to retrieve MX record.");

        // ソート
        asort($json["MX"]);

        // MXレコードを定義
        $mx = [];

        // MXレコードを追加
        foreach ($json["MX"] as $m) {
            
            // もし、targetが存在するなら
            if( $m["target"] != null && !empty($m["target"]) ){

                // MXレコードに追加
                array_push($mx, $m);
            }
        }
    }

    // もし、JSONデータの取得を失敗したなら
    else  array_push($error, "Invalid domain. [ $domain ]");

    // もし、今のところエラーがないなら
    if( count($error) == 0 ){

        // MXレコードを繰り返す
        foreach($mx as $m){

            // MXレコードのサーバーにtelnet接続をする
            $socket = @fsockopen($m["target"], 25);

            // もし、接続できたら
            if($socket){

                // localhostに挨拶する
                fwrite($socket, "helo localhost\n");

                // メールの送り主を設定する
                fwrite($socket, "mail from:<info@itsystem-lab.com>\n");

                // 宛先のメールアドレスの存在確認
                fwrite($socket, "rcpt to:<$email>\n");

                // // ソケットのレスポンスを回収 (4つ)
                for ($i=0; $i < 4 ; $i++) { 
                    
                    // 回収したレスポンスを $lineに代入
                    $line = fread($socket, 2048);

                    // もし、lineに何も入っていないなら
                    if($line == null || $line == ""){

                        // 次のループへ
                        break;
                    }

                    // もし、\r\nが含まれているなら
                    if( strpos($line, "\r\n") ){

                        // \r\nで分割する
                        $line = explode("\r\n", $line);

                        // \r\nで分割したものを繰り返す
                        foreach ($line as $l) {

                            // もし、$lに何も入っていないなら
                            if($l == null || $l == ""){

                                // 次のループへ
                                break;
                            }
                            
                            // もしレスポンスが 2 から始まっていないなら
                            if( substr($l, 0, 1) !== "2" ){

                                // エラー文を追加
                                array_push($error, $l);
                            }

                            // ログに残す
                            array_push($log, $l);
                        }
                        
                    }

                    // それ以外なら
                    else{

                        // もしレスポンスが 2 から始まっていないなら
                        if( substr($line, 0, 1) !== "2" ){

                            // エラー文を追加
                            array_push($error, $line);
                        }

                        // ログに残す
                        array_push($log, $line);
                    }
                }

                // もし、今のところエラーがないなら ループ終了
                if( count($error) == 0 ) {

                    // メールサーバーを記録
                    $mx_server = $m["target"];

                    // ループ終了
                    break;
                }
            }

            // もし、接続できなかったら
            else array_push($error, "Could not connect to ".$m["target"].":25.");
        }
    }
}

// 構文チェックでエラーがあった
else{

    // 構文をfalseにする
    $syntax = false;
}

// レスポンスを定義
$response = [];

// もし、エラーがないなら
if( count($error) == 0 ){

    // ステータスを成功(true)にする
    $response["status"] = true;
}

// それ以外なら
else{

    // ステータスを成功(false)にする
    $response["status"] = false;

    // サンプルを追加する
    array_push($error, "Example: ".$full_url."sample@sample.com");
}

// 構文チェックの結果を出力
$response["syntax"] = $syntax;

// タイムスタンプを出力
$response["timestamp"] = time();

// メアドを出力
$response["email-address"] = $email;

// ユーザーを出力
$response["user"] = $user;

// ドメインを出力
$response["domain"] = $domain;

// メールサーバーを出力
$response["mail-server"] = $mx_server??"Not found.";

// ログを収納
$response["log"] = $log;

// エラーを収納
$response["error"] = $error;

// 出力
print json_encode($response, JSON_PRETTY_PRINT);

?>
