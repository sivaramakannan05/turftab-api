<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php if(!empty($type)) echo strtolower($type); ?></title>
  </head>
  <body>
    <div style="background-color:#fff;width: 600px;margin: 0 auto">
      <div style="background-color:#06b927;padding: 5px 10px;">
        <img src="http://temp1.pickzy.com/turf_tab/assets/images/logo.png" alt="Logo" title="Logo" style="height: 100px;display: block;float: left" /> 
        <h2 style="float: right;font-size: 32px;color: #fff;padding-right: 10px;"> <?php if(!empty($type)) echo $type; ?> </h2>
        <div style="clear: both;"> </div>
      </div>
      <div style="background-color:#fff;">
        <p> Hi <b> <?php if(!empty($username)) echo $username; ?>, </b> </p>
        <p> OTP for <?php if(!empty($type)) echo strtolower($type); ?> is below.</p>
        <a style="background: #06b927;color: #fff;cursor: text;padding: 14px;text-decoration: none;border-radius: 30px;font-weight: bold;border: 1px solid grey;display: inline-block;"> <?php if(!empty($otp)) echo $otp; ?> </a>
        <p>Good luck!.</p>
      </div>
      <div style="background-color:#06b927;padding: 10px;color: #fff;">
        <div style="width: 50%;display: inline-block;">
          <p style="margin: 0;"> Follow us on </p>
          <p style="margin: 5px 0px 0px 0px;">
            <a href="https://www.facebook.com/" style="background: #3B5998;color:#fff;text-decoration:none;padding: 3px;text-align: center;border-radius: 6px;margin-right: 8px;"> <img src="http://temp1.pickzy.com/turf_tab/assets/images/facebook.png" alt="facebook" title="facebook" style="height: 14px;margin-left: 3px;" /> </a>
            <a href="https://twitter.com/" style="background: #2FC2EF;color:#fff;text-decoration:none;padding: 3px;text-align: center;border-radius: 6px;margin-right: 8px;"> <img src="http://temp1.pickzy.com/turf_tab/assets/images/twitter.png" alt="twitter" title="twitter" style="height: 14px;margin-left: 3px;" /> </a>
            <a href="https://www.linkedin.com/" style="background: #0077B5;color:#fff;text-decoration:none;padding: 3px;text-align: center;border-radius: 6px;"> <img src="http://temp1.pickzy.com/turf_tab/assets/images/linkedin.png" alt="linkedin" title="linkedin" style="height: 14px;margin-left: 3px;" /> </a>
          </p>
        </div>
        <div style="float: right;">
          <p style="margin: 0"> yyyyyyyyyy, </p>
          <p style="margin: 0"> zzzzzzzz. </p>
        </div>
        <div style="clear: both;"> </div>
      </div>
    </div>
  </body>
</html>
