<!DOCTYPE html>
<html lang="en">

<head>
  <?php
  require 'vendor/autoload.php';
  include "db.php";

  // ✅ 設定你的 Google 客戶端 ID
  $google_client_id = "121174988046-dcn0gp3vkqa12jt4c7l7imatp8p6j2jm.apps.googleusercontent.com";
  $line_client_id = '2006972500';
  $line_redirect_uri = urlencode('http://demo2.im.ukn.edu.tw/~meridians/login-line.php'); // 替換成你的 LINE 回傳網址
  // 為防 CSRF 攻擊，產生一組隨機 state
  $line_state = bin2hex(random_bytes(8));
  $_SESSION['line_state'] = $line_state;

  // 建立 LINE 授權網址，包含必要參數
  $line_login_url = "https://access.line.me/oauth2/v2.1/authorize?response_type=code&client_id={$line_client_id}&redirect_uri={$line_redirect_uri}&state={$line_state}&scope=openid%20profile%20email";
  ?>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="Template Mo">
  <link href="https://fonts.googleapis.com/css?family=Poppins:100,200,300,400,500,600,700,800,900" rel="stylesheet">
  <link rel="icon" href="assets/images/logo.ico" type="image/x-icon" />

  <title>登入/註冊</title>
  <!-- Bootstrap core CSS -->
  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <!-- Additional CSS Files -->
  <link rel="stylesheet" href="assets/css/fontawesome.css">
  <link rel="stylesheet" href="assets/css/templatemo-edu-meeting.css">
  <link rel="stylesheet" href="assets/css/owl.css">
  <link rel="stylesheet" href="assets/css/lightbox.css">

  <script src="https://accounts.google.com/gsi/client" async defer></script>

  <script>
    function handleCredentialResponse(response) {
      console.log("Google JWT Token: ", response.credential);

      // 發送 JWT Token 到 PHP 處理
      fetch("login-google.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: "idtoken=" + response.credential
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            window.location.href = data.redirect; // 登入成功後導向對應的頁面
          } else {
            alert("請填寫完整資訊才能完成註冊！");
            window.location.href = data.redirect; // 跳轉到補充資料頁面
          }
        });
    }
  </script>



  <style>
    /* 彈出視窗樣式 */
    .popup {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 400px;
      background: white;
      padding: 20px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
      border-radius: 8px;
      text-align: center;
    }

    .popup h2 {
      margin-bottom: 10px;
    }

    .popup input,
    .popup select {
      width: 100%;
      padding: 8px;
      margin: 8px 0;
    }

    .popup button {
      width: 100%;
      padding: 10px;
      background: blue;
      color: white;
      border: none;
      cursor: pointer;
    }

    /* checkbox 被註解 */
    [type="checkbox"]:checked,
    [type="checkbox"]:not(:checked) {
      position: absolute;
      left: -9999px;
    }

    .checkbox:checked+label,
    .checkbox:not(:checked)+label {
      position: relative;
      display: block;
      text-align: center;
      width: 60px;
      height: 16px;
      border-radius: 8px;
      padding: 0;
      margin: 10px auto;
      cursor: pointer;
      background-color: #ffeba7;
    }

    .checkbox:checked+label:before,
    .checkbox:not(:checked)+label:before {
      position: absolute;
      display: block;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      color: #ffeba7;
      background-color: #102770;
      font-family: 'unicons';
      content: '\eb4f';
      z-index: 20;
      top: -10px;
      left: -10px;
      line-height: 36px;
      text-align: center;
      font-size: 24px;
      transition: all 0.5s ease;
    }

    .checkbox:checked+label:before {
      transform: translateX(44px) rotate(-270deg);
    }

    /* google  icon */
    .gsi-material-button {
      -moz-user-select: none;
      -webkit-user-select: none;
      -ms-user-select: none;
      -webkit-appearance: none;
      background-color: #f2f2f2;
      background-image: none;
      border: none;
      -webkit-border-radius: 20px;
      border-radius: 20px;
      -webkit-box-sizing: border-box;
      box-sizing: border-box;
      color: #1f1f1f;
      cursor: pointer;
      font-family: 'Roboto', arial, sans-serif;
      font-size: 14px;
      height: 40px;
      letter-spacing: 0.25px;
      outline: none;
      overflow: hidden;
      padding: 0 12px;
      position: relative;
      text-align: center;
      -webkit-transition: background-color .218s, border-color .218s, box-shadow .218s;
      transition: background-color .218s, border-color .218s, box-shadow .218s;
      vertical-align: middle;
      white-space: nowrap;
      width: auto;
      max-width: 400px;
      min-width: min-content;
    }

    .gsi-material-button .gsi-material-button-icon {
      height: 20px;
      margin-right: 12px;
      min-width: 20px;
      width: 20px;
    }

    .gsi-material-button .gsi-material-button-content-wrapper {
      -webkit-align-items: center;
      align-items: center;
      display: flex;
      -webkit-flex-direction: row;
      flex-direction: row;
      -webkit-flex-wrap: nowrap;
      flex-wrap: nowrap;
      height: 100%;
      justify-content: space-between;
      position: relative;
      width: 100%;
    }

    .gsi-material-button .gsi-material-button-contents {
      -webkit-flex-grow: 1;
      flex-grow: 1;
      font-family: 'Roboto', arial, sans-serif;
      font-weight: 500;
      overflow: hidden;
      text-overflow: ellipsis;
      vertical-align: top;
    }

    .gsi-material-button .gsi-material-button-state {
      -webkit-transition: opacity .218s;
      transition: opacity .218s;
      bottom: 0;
      left: 0;
      opacity: 0;
      position: absolute;
      right: 0;
      top: 0;
    }

    .gsi-material-button:disabled {
      cursor: default;
      background-color: #ffffff61;
    }

    .gsi-material-button:disabled .gsi-material-button-state {
      background-color: #1f1f1f1f;
    }

    .gsi-material-button:disabled .gsi-material-button-contents {
      opacity: 38%;
    }

    .gsi-material-button:disabled .gsi-material-button-icon {
      opacity: 38%;
    }

    .gsi-material-button:not(:disabled):active .gsi-material-button-state,
    .gsi-material-button:not(:disabled):focus .gsi-material-button-state {
      background-color: #001d35;
      opacity: 12%;
    }

    .gsi-material-button:not(:disabled):hover {
      -webkit-box-shadow: 0 1px 2px 0 rgba(60, 64, 67, .30), 0 1px 3px 1px rgba(60, 64, 67, .15);
      box-shadow: 0 1px 2px 0 rgba(60, 64, 67, .30), 0 1px 3px 1px rgba(60, 64, 67, .15);
    }

    .gsi-material-button:not(:disabled):hover .gsi-material-button-state {
      background-color: #001d35;
      opacity: 8%;
    }

    .container1 {
      margin-top: -150px;
    }

    .card-front {
      margin-top: -40px;
    }

    /* 確保 Bootstrap 的 row 生效，並在左右欄位之間留一些間距 */
    .row {
      margin: 0;
    }

    /* 調整 LINE 按鈕樣式 */
    .btn-line {
      display: inline-block;
      width: 100%;
      padding: 10px 20px;
      background-color: #00C300;
      /* LINE 綠色 */
      color: #fff;
      text-align: center;
      font-size: 16px;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      transition: background-color 0.3s ease;
      line-height: 1.5;
      /* 讓按鈕在 col-6 中可撐滿整個寬度 */
      box-sizing: border-box;
    }

    .btn-line:hover {
      background-color: #00A800;
    }

    /* Google 按鈕區域，如有需要可再加其他調整 */
    .g_id_signin {
      /* 讓 Google 按鈕在設定的寬高下自動排版 */
      display: block;
      margin: auto;
    }
  </style>

</head>

<body>

  <!-- Header Area Start -->
  <?php include("head.php"); ?>
  <!-- Header Area End -->

  <!-- ***** Header Area End ***** -->

  <section class="heading-page header-text" id="top">
    <div class="section">
      <div class="container1">
        <div class="row full-height justify-content-center">
          <div class="col-12 text-center align-self-center py-5">
            <div class="section pb-5 pt-5 pt-sm-2 text-center">
              <h6 class="mb-0 pb-3"><span>登入</span><span>註冊</span></h6>
              <input class="checkbox" type="checkbox" id="reg-log" name="reg-log" />
              <label for="reg-log"></label>
              <div class="card-3d-wrap mx-auto">
                <div class="card-3d-wrapper">

                  <!-- 登入表單 -->
                  <!-- 登入表單 -->
                  <div class="card-front">
                    <div class="center-wrap">
                      <form action="login-b.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="post">
                        <div class="section text-center">
                          <h4 class="mb-4 pb-3">登入</h4>
                          <div class="form-group">
                            <input type="text" name="email" class="form-style" placeholder="Email帳號" required>
                          </div>
                          <div class="form-group mt-2">
                            <input type="password" name="pwd" class="form-style" placeholder="密碼" required>
                          </div>
                          <div class="button-group">
                            <button type="submit" class="btn btn-primary">登入</button>
                          </div>
                        </div>
                      </form>

                      <!-- 水平排列的第三方登入按鈕，使用 Bootstrap row 與 col-6 -->
                      <div class="row align-items-center justify-content-center" style="margin-top: 10px;">
                        <div class="row">
                          <!-- Google 登入按鈕區塊 -->
                          <div class="col-6">
                            <!-- Google 按鈕依然使用 Google 的 JS 產生 -->
                            <div id="g_id_onload"
                              data-client_id="<?= $google_client_id ?>"
                              data-callback="handleCredentialResponse">
                            </div>
                            <div class="g_id_signin" data-type="standard" data-shape="rectangular" style="width: 100%;"></div>
                          </div>
                          <!-- LINE 登入按鈕區塊 -->
                          <div class="col-6">
                            <a href="<?php echo $line_login_url; ?>" class="btn btn-line">
                              <i class="fab fa-line"></i> LINE 登入
                            </a>
                          </div>
                        </div>
                      </div>

                      <p class="mb-0 mt-4 text-center">
                        <a href="forget-pwd.php" class="link">忘記密碼</a>
                      </p>
                    </div>
                  </div>



                  <!-- Google 註冊 -->
                  <div class="card-back">
                    <div class="center-wrap">
                      <div class="section text-center">
                        <h5>選擇註冊方式</h5>
                        <br>
                        <!-- 使用 bootstrap 的 row 與 col-6 排列兩個按鈕 -->
                        <div class="row">
                          <!-- Google 登入按鈕區塊 -->
                          <div class="col-6">
                            <!-- Google 按鈕依然使用 Google 的 JS 產生 -->
                            <div id="g_id_onload"
                              data-client_id="<?= $google_client_id ?>"
                              data-callback="handleCredentialResponse">
                            </div>
                            <div class="g_id_signin" data-type="standard" data-shape="rectangular" style="width: 100%;"></div>
                          </div>
                          <!-- LINE 登入按鈕區塊 -->
                          <div class="col-6">
                            <a href="<?php echo $line_login_url; ?>" class="btn btn-line">
                              <i class="fab fa-line"></i> LINE 登入
                            </a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

  </section>


  <div class="footer">
    <p>Copyright © 2022 Edu Meeting Co., Ltd. All Rights Reserved.
      <br>Design: <a href="https://templatemo.com/page/1" target="_parent" title="website templates">TemplateMo</a>
    </p>
  </div>



  <!-- Scripts -->
  <!-- Bootstrap core JavaScript -->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <script src="assets/js/isotope.min.js"></script>
  <script src="assets/js/owl-carousel.js"></script>
  <script src="assets/js/lightbox.js"></script>
  <script src="assets/js/tabs.js"></script>
  <script src="assets/js/isotope.js"></script>
  <script src="assets/js/video.js"></script>
  <script src="assets/js/slick-slider.js"></script>
  <script src="assets/js/custom.js"></script>

  <script>
    //according to loftblog tut
    $('.nav li:first').addClass('active');

    var showSection = function showSection(section, isAnimate) {
      var
        direction = section.replace(/#/, ''),
        reqSection = $('.section').filter('[data-section="' + direction + '"]'),
        reqSectionPos = reqSection.offset().top - 0;

      if (isAnimate) {
        $('body, html').animate({
            scrollTop: reqSectionPos
          },
          800);
      } else {
        $('body, html').scrollTop(reqSectionPos);
      }

    };

    var checkSection = function checkSection() {
      $('.section').each(function() {
        var
          $this = $(this),
          topEdge = $this.offset().top - 80,
          bottomEdge = topEdge + $this.height(),
          wScroll = $(window).scrollTop();
        if (topEdge < wScroll && bottomEdge > wScroll) {
          var
            currentId = $this.data('section'),
            reqLink = $('a').filter('[href*=\\#' + currentId + ']');
          reqLink.closest('li').addClass('active').
          siblings().removeClass('active');
        }
      });
    };

    $('.main-menu, .responsive-menu, .scroll-to-section').on('click', 'a', function(e) {
      e.preventDefault();
      showSection($(this).attr('href'), true);
    });

    $(window).scroll(function() {
      checkSection();
    });
  </script>
</body>


</body>

</html>