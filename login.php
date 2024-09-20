<?php
session_start();
$connection = pg_connect("host=localhost dbname=login user=postgres password=abi13");
if (!$connection) {
    echo "An error has occurred while connecting to the database.<br>";
    exit;
}
$message = ""; 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $designation = $_POST['designation'];
    $query = "SELECT * FROM login WHERE username = '$username' AND password = '$password' AND designation = '$designation'";
    $result = pg_query($connection, $query);
    if ($result && pg_num_rows($result) > 0) {
        $_SESSION['username'] = $username;
        $_SESSION['designation'] = $designation;
        if ($designation == 'employee') {
            header("Location: useremp.php");
        } else if($designation == 'manager'){
            header("Location: usermgr.php");
        }
        exit();
    } else 
        $message = "Invalid details. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trust Bank</title>
    <link rel="shortcut icon" type="image/icon" href="assets/images/favicon.png" />
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/slick.css" rel="stylesheet">
    <link href="assets/css/magnific-popup.css" rel="stylesheet">
    <link id="switcher" href="assets/css/theme-color/default-theme.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,400i,600,700,800" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<style>
*{
    box-sizing: border-box;
}
#mu-header{
    background-color: #339999;
} 
#submit-btn {
	border-radius: 4px;
	border: 1px solid #888;
	background-color: transparent;
	color: #555;
	font-size: 13px;
	font-weight: 600;
	letter-spacing: 1px;
	text-align: center;
	padding: 14px 24px;
	margin-top: 10px;
	-webkit-transition: all 0.5s;
	transition: all 0.5s;
	width: 130px;
}
#submit-btn:hover,
#submit-btn:focus {
	background-color: #339999;
	color: #fff;
}
input[type="text"],input[type="password"]{
    border-radius: 4px;
	border: 1px solid #888;
	background-color: transparent;
	color: #555;
	font-size: 13px;
	font-weight: 600;
	letter-spacing: 1px;
	text-align: center;
	padding: 14px 24px;
	margin-top: 10px;
	-webkit-transition: all 0.5s;
	transition: all 0.5s;
	width: 200px;
    height: 25px;
}
#designation{
    border-radius: 4px;
	border: 1px solid #888;
	background-color: transparent;
	color: #555;
	font-size: 13px;
	font-weight: 600;
	letter-spacing: 1px;
	text-align: center;
	padding: 14px 24px;
	margin-top: 10px;
	-webkit-transition: all 0.5s;
	transition: all 0.5s;
	width: 150px;
}
</style>
<body><br>
    <header id="mu-header" class="" role="banner">
        <div class="container">
            <nav class="navbar navbar-default mu-navbar">
                <div class="container-fluid">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                            data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>
                        <a class="navbar-brand" href="index.html"><img src="assets/images/logo.png"></a>
                        <a class="navbar-brand" href="index.html"><small>
                                <div class="logo">Trust Bank</div>
                            </small></a>
                    </div>
                </div>
            </nav>
        </div>
    </header>
    <section id="mu-service">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="mu-service-area">
                        <div class="mu-service-header"><br>
                            <div><?php echo $message; ?></div><br>
                            <h2 class="mu-heading-title">LOGIN</h2>
                            <span class="mu-header-dot"></span><br>
                            <form action="login.php" method="POST">
                                <label for="username">Enter username:</label><br>
                                <input type="text" id="username" name="username" required>
                                <br><br>
                                <label for="password">Enter password:</label><br>
                                <input type="password" id="password" name="password" required>
                                <br><br>
                                <label for="designation">Login as:</label><br>
                                <select id="designation" name="designation" required>
                                    <option value="employee">Employee</option>
                                    <option value="manager">Manager</option>
                                </select>
                                <br><br>
                                <input type="submit" value="Login" id="submit-btn">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <footer id="mu-footer" role="contentinfo">
        <div class="container">
            <div class="mu-footer-area">
                <p class="mu-copy-right">&copy; Copyright | Trust bank | All
                    rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="assets/js/slick.min.js"></script>
    <script type="text/javascript" src="assets/js/jquery.filterizr.min.js"></script>
    <script type="text/javascript" src="assets/js/jquery.magnific-popup.min.js"></script>
    <script type="text/javascript" src="assets/js/app.js"></script>
    <script type="text/javascript" src="assets/js/typed.min.js"></script>
    <script src="assets/js/jquery.appear.js"></script>
    <script type="text/javascript" src="assets/js/jquery.lineProgressbar.js"></script>
    <script type="text/javascript" src="assets/js/custom.js"></script>
</body>
</html>