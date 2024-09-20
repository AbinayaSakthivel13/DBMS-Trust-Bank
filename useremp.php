<?php
session_start();
if (!isset($_SESSION['username']) || empty($_SESSION['username']) || $_SESSION['designation'] != 'employee') {
    die("Access denied.");
}
$username = $_SESSION['username'];
$connection = pg_connect("host=localhost dbname=trust_bank user=postgres password=abi13");
if (!$connection) {
    echo "An error has occurred while connecting to the database.<br>";
    exit;
}
$message = "";
$emp_query = "SELECT emp_id, branch_id FROM employee WHERE name = $1";
$emp_result = pg_query_params($connection, $emp_query, array($username));
if ($emp_result && pg_num_rows($emp_result) > 0) {
    $emp_row = pg_fetch_assoc($emp_result);
    $emp_id = $emp_row['emp_id'];
    $branch_id = $emp_row['branch_id'];
} else {
    die("Employee not found.");
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_customer'])) {
        $name = $_POST['name'];
        $accno = $_POST['accno'];
        $acctype = $_POST['acctype'];
        $balance = $_POST['balance'];
        $age = $_POST['age'];
        $gender = $_POST['gender'];
        $address = $_POST['address'];
        $contact = $_POST['contact'];
        if ($age <= 18) {
            $message = "Failed to add customer: Age must be greater than 18.";
        } else {
            $query = "INSERT INTO customer (cus_name, acc_no, acc_type, balance, age, gender, address, contact, emp_id, branch_id) 
                      VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)";
            $result = @pg_query_params($connection, $query, array($name, $accno, $acctype, $balance, $age, $gender, $address, $contact, $emp_id, $branch_id));
            if ($result) {
                $message = "Customer added successfully";
            } else {
                $error = pg_last_error($connection);
                if (strpos($error, 'duplicate key value') !== false) {
                    $message = "Failed to add customer: Account number already exists.";
                } else {
                    $message = "Failed to add customer: " . $error;
                }
            }
        }
    } elseif (isset($_POST['remove_customer'])) {
        $accno = $_POST['accno_remove'];
        $query = "DELETE FROM customer WHERE acc_no = $1";
        $result = pg_query_params($connection, $query, array($accno));
        if ($result && pg_affected_rows($result) > 0) {
            $message = "Customer removed successfully";
        } else {
            $message = "Invalid account number";
        }
    } elseif (isset($_POST['make_transaction'])) {
        $amount = $_POST['amount'];
        $accno = $_POST['accno_trans'];
        $trans_mode = $_POST['trans_mode'];
        $trans_date = $_POST['trans_date'];
        pg_query($connection, "BEGIN");
        $balance_query = "SELECT balance FROM customer WHERE acc_no = $1";
        $balance_result = pg_query_params($connection, $balance_query, array($accno));
        if ($balance_result && pg_num_rows($balance_result) > 0) {
            $balance_row = pg_fetch_assoc($balance_result);
            $current_balance = $balance_row['balance'];
            if ($trans_mode == 'withdrawal') {
                if ($amount > $current_balance) {
                    $message = "Insufficient balance";
                } else {
                    $new_balance = $current_balance - $amount;
                    $update_balance_query = "UPDATE customer SET balance = $1 WHERE acc_no = $2";
                    $update_balance_result = pg_query_params($connection, $update_balance_query, array($new_balance, $accno));
                    if ($update_balance_result) {
                        $insert_trans_query = "INSERT INTO transaction (amount, trans_mode, trans_date, acc_no) VALUES ($1, $2, $3, $4) RETURNING trans_id";
                        $insert_trans_result = pg_query_params($connection, $insert_trans_query, array($amount, $trans_mode, $trans_date, $accno));
                        if ($insert_trans_result) {
                            $trans_row = pg_fetch_assoc($insert_trans_result);
                            $trans_id = $trans_row['trans_id'];
                            $message = "Transaction successful. Transaction ID: $trans_id";
                            pg_query($connection, "COMMIT");
                        } else {
                            $message = "Transaction failed";
                            pg_query($connection, "ROLLBACK");
                        }
                    } else {
                        $message = "Transaction failed";
                        pg_query($connection, "ROLLBACK");
                    }
                }
            } elseif ($trans_mode == 'deposit') {
                $new_balance = $current_balance + $amount;
                $update_balance_query = "UPDATE customer SET balance = $1 WHERE acc_no = $2";
                $update_balance_result = pg_query_params($connection, $update_balance_query, array($new_balance, $accno));
                if ($update_balance_result) {
                    $insert_trans_query = "INSERT INTO transaction (amount, trans_mode, trans_date, acc_no) VALUES ($1, $2, $3, $4) RETURNING trans_id";
                    $insert_trans_result = pg_query_params($connection, $insert_trans_query, array($amount, $trans_mode, $trans_date, $accno));
                    if ($insert_trans_result) {
                        $trans_row = pg_fetch_assoc($insert_trans_result);
                        $trans_id = $trans_row['trans_id'];
                        $message = "Transaction successful. Transaction ID: $trans_id";
                        pg_query($connection, "COMMIT");
                    } else {
                        $message = "Transaction failed";
                        pg_query($connection, "ROLLBACK");
                    }
                } else {
                    $message = "Transaction failed";
                    pg_query($connection, "ROLLBACK");
                }
            }
        } else {
            $message = "Invalid account number";
            pg_query($connection, "ROLLBACK");
        }
    }
    elseif (isset($_POST['view_customer'])) {
        $accno_view = $_POST['accno_view'];
        $query_view = "SELECT cus_name, acc_no, balance, age, gender, address, contact, acc_type FROM customer WHERE acc_no = $1";
        $result_view = pg_query_params($connection, $query_view, array($accno_view));
        if ($result_view && pg_num_rows($result_view) > 0) {
            $customer_details = pg_fetch_assoc($result_view);
        } else {
            $message = "Invalid account number";
            $customer_details = null; 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee Dashboard</title>
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
#submit-btn, #remove-btn {
    border-radius: 4px;
    border: 1px solid #888;
    background-color: #339999;
    color: #fff;
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
#submit-btn:hover, #submit-btn:focus,
#remove-btn:hover, #remove-btn:focus {
    background-color: transparent;
    color: #339999;;
}
input[type="text"], input[type="number"], select {
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
#mu-header {
	display: inline;
	float: left;
	width: 100%;
	position: absolute;
	left: 0;
	right: 0;
	top: 0;
	padding: 25px 0;
	z-index: 999;
	transition: all 0.5s;
}
.mu-service-header{
    background-color: #f8f8f8;
    border-radius:20px;
    border: 1px solid grey;
    
}
input[type="text"],input[type="number"]{
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
	width: 300px;
    height: 35px;
}
#acctype,#gender,#trans_mode{
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
	width: 170px;
    height: 50px;
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
                        <a class="navbar-brand"><img src="assets/images/logo.png"></a>
                        <a class="navbar-brand"><small>
                                <div class="logo">Trust Bank</div>
                            </small></a>
                    </div>
                    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                        <ul class="nav navbar-nav mu-menu navbar-right">
                            <li><b><div style="color:white; margin-top:6px;"><?php echo "Welcome $username"; ?></div></b></li>
                            <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i></a></li> 
                        </ul>
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
                    <br><center><h2 class="mu-heading-title">Employee Dashboard</h2></center><br>
                        <div class="mu-service-header">
                            <span class="mu-header-dot"></span><br>
                            <div><?php echo $message; ?></div><br>
                            <h3>Add Customer</h3><hr style="width:50%; border-top: 1px solid black;">
                            <form action="useremp.php" method="POST">
                                <label for="name">Enter Name:</label><br>
                                <input type="text" id="name" name="name" required><br><br>
                                <label for="accno">Enter Account Number:</label><br>
                                <input type="text" id="accno" name="accno" required><br><br>
                                <label for="acctype">Select Account Type:</label><br>
                                <select id="acctype" name="acctype" required>
                                    <option value="savings">Savings</option>
                                    <option value="current">Current</option>
                                </select><br><br>
                                <label for="balance">Enter Balance:</label><br>
                                <input type="number" id="balance" name="balance" required><br><br>
                                <label for="age">Enter Age:</label><br>
                                <input type="number" id="age" name="age" required><br><br>
                                <label for="gender">Select Gender:</label><br>
                                <select id="gender" name="gender" required>
                                    <option value="M">Male</option>
                                    <option value="F">Female</option>
                                </select><br><br>
                                <label for="address">Enter Address:</label><br>
                                <input type="text" id="address" name="address" required><br><br>
                                <label for="contact">Enter Contact:</label><br>
                                <input type="text" id="contact" name="contact" required><br><br>
                                <input type="submit" name="add_customer" value="Add" id="submit-btn"><br>
                            </form>
                            <br><br>
                            <h3>Remove Customer</h3><hr style="width:50%; border-top: 1px solid black;">
                            <form action="useremp.php" method="POST">
                                <label for="accno_remove">Enter Account Number:</label><br>
                                <input type="text" id="accno_remove" name="accno_remove" required><br><br>
                                <input type="submit" name="remove_customer" value="Remove" id="remove-btn"><br><br>
                            </form>
                            <br><br>
                            <h3>Make Transaction</h3><hr style="width:50%; border-top: 1px solid black;">
                            <form action="useremp.php" method="POST">
                                <label for="amount">Enter Amount:</label><br>
                                <input type="number" id="amount" name="amount" required><br><br>
                                <label for="accno_trans">Enter Account Number:</label><br>
                                <input type="text" id="accno_trans" name="accno_trans" required><br><br>
                                <label for="trans_mode">Select Transaction Mode:</label><br>
                                <select id="trans_mode" name="trans_mode" required>
                                    <option value="deposit">Deposit</option>
                                    <option value="withdrawal">Withdrawal</option>
                                </select><br><br>
                                <label for="trans_date">Enter Transaction Date:</label><br>
                                <input type="date" id="trans_date" name="trans_date" required><br><br>
                                <input type="submit" name="make_transaction" value="Submit" id="submit-btn"><br>
                            </form><br><br>
                            <h3>View Customer Details</h3><hr style="width:50%; border-top: 1px solid black;">
                            <form action="useremp.php" method="POST">
                                <label for="accno_view">Enter Account Number:</label><br>
                                <input type="text" id="accno_view" name="accno_view" required><br><br>
                                <input type="submit" name="view_customer" value="View Details" id="submit-btn"><br><br>
                            </form>
                            <br><br>
                            <?php if(isset($customer_details)): ?>
                                    <h3>Customer Details</h3><hr style="width:50%; border-top: 1px solid black;">
                                    <p>Name: <?php echo $customer_details['cus_name']; ?></p>
                                    <p>Account Number: <?php echo $customer_details['acc_no']; ?></p>
                                    <p>Balance: <?php echo $customer_details['balance']; ?></p>
                                    <p>Age: <?php echo $customer_details['age']; ?></p>
                                    <p>Gender: <?php echo $customer_details['gender']; ?></p>
                                    <p>Address: <?php echo $customer_details['address']; ?></p>
                                    <p>Contact: <?php echo $customer_details['contact']; ?></p>
                                    <p>Account Type: <?php echo $customer_details['acc_type']; ?></p>
                                <?php endif; ?><br>
                            <br><br>
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
