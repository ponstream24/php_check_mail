# PHPでメールの存在(生存)確認をする

こちらのコードは、ITシステムラボの[APIサービス](https://api.itsystem-lab.com/)にて実装しています。

## 使用例
> https://api.itsystem-lab.com/emailCheck/<メールアドレス> <br>
> https://api.itsystem-lab.com/emailCheck/info@itysytem-lab.com <br>

## 取得できる情報(レスポンス)
Json型での出力になります。<br>
また、このシステムで取得できる情報は以下の通りです。
<table>
 <thead>
   <tr>
       <th colspan="3">emailCheck</th>
   </tr>
   <tr>
       <th>Key</th>
       <th>型</th>
       <th>Value</th>
   </tr>
 </thead>
 <tbody>
   <tr>
       <td>status</td>
       <td>boolean型</td>
       <td>存在のステータス
        <ul>
          <li>true: 存在するするかも</li>
          <li>false: 存在しないかも</li>
        </ul>
       </td>
   </tr>
   <tr>
       <td>syntax</td>
       <td>boolean型</td>
       <td>メールアドレスの構成が正しいか
        <ul>
          <li>true: 正しい</li>
          <li>false: 誤りがある</li>
        </ul>
       </td>
   </tr>
   <tr>
       <td>timestamp</td>
       <td>int型</td>
       <td>UNIXタイムスタンプ</td>
   </tr>
   <tr>
       <td>email-address</td>
       <td>string型</td>
       <td>確認したメールアドレス</td>
   </tr>
   <tr>
       <td>user</td>
       <td>string型</td>
       <td>メールアドレスのユーザー部分</td>
   </tr>
   <tr>
       <td>domain</td>
       <td>string型</td>
       <td>メールアドレスのドメイン部分</td>
   </tr>
   <tr>
       <td>mail-server</td>
       <td>string型</td>
       <td><メールアドレスチェックに使用したメールサーバー(MXレコード)/td>
   </tr>
   <tr>
       <td>log</td>
       <td>array型</td>
       <td>メールサーバー(MXレコード)にアクセスした際のログ</td>
   </tr>
   <tr>
       <td>error</td>
       <td>array型</td>
       <td>エラー</td>
   </tr>
 </tbody>
</table>

## レスポンス例
```json
{
    "status": true,
    "syntax": true,
    "timestamp": 1656474177,
    "email-address": "info@itsystem-lab.com",
    "user": "info",
    "domain": "itsystem-lab.com",
    "mail-server": "〇〇〇〇",
    "log": [
        "220 〇〇〇〇〇〇〇〇〇〇〇〇〇〇〇〇",
        "250 〇〇〇〇〇〇〇〇〇〇〇〇〇〇〇〇",
        "250 〇〇〇〇〇〇〇〇〇〇〇〇〇〇〇〇",
        "250 〇〇〇〇〇〇〇〇〇〇〇〇〇〇〇〇"
    ],
    "error": []
}
```

## 解説
このシステムは、PHPを使用しており、[APIサービス(DNSレコードの取得)](https://api.itsystem-lab.com/dns) を使用しております。

## 注意点
このソースを使用する際は、必ず `.htaccess`も設置してください。
その際は、8行目のパスを書き換えてください。
```.htaccess
RewriteRule . /emailCheck/index.php [L]  # ここを書き換えてください。
```
 <br> <br>
> ITシステムラボ : https://www.itsystem-lab.com/
```
©︎Copyright All Rights Reserved ITsystemLab
```
