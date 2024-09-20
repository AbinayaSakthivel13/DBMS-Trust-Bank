<?php
session_start();
if (!isset($_SESSION['username']) || empty($_SESSION['username']) || $_SESSION['designation'] != 'manager') {
    die("Access denied.");
}
$username = $_SESSION['username'];
$connection = pg_connect("host=localhost dbname=trust_bank user=postgres password=abi13");
if (!$connection) {
    echo "An error has occurred while connecting to the database.<br>";
    exit;
}
$message = "";
$customer_details = [];
$employee_details = [];
$loan_details = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_employee'])) {
        $emp_id = $_POST['emp_id'];
        $name = $_POST['name'];
        $manager_query = "SELECT mgr_id, branch_id FROM manager WHERE name = $1";
        $manager_result = pg_query_params($connection, $manager_query, array($username));
        if ($manager_result && pg_num_rows($manager_result) > 0) {
            $manager_row = pg_fetch_assoc($manager_result);
            $mgr_id = $manager_row['mgr_id'];
            $branch_id = $manager_row['branch_id'];
            $emp_query = "SELECT emp_id FROM employee WHERE emp_id = $1";
            $result = pg_query_params($connection, $emp_query, array($emp_id));
            if (pg_num_rows($result) > 0) {
                $message = "Employee ID already exists.";
            } else {
                $query = "INSERT INTO employee (emp_id, name, mgr_id, branch_id) VALUES ($1, $2, $3, $4)";
                $result = pg_query_params($connection, $query, array($emp_id, $name, $mgr_id, $branch_id));
                if ($result) {
                    $message = "Employee added successfully";
                    $login_connection = pg_connect("host=localhost dbname=login user=postgres password=abi13");
                    $default_password = $name . "123*";
                    $insert_login_query = "INSERT INTO login (username, password, designation) VALUES ($1, $2, 'employee')";
                    $insert_login_result = pg_query_params($login_connection, $insert_login_query, array($name, $default_password));
                    if (!$insert_login_result) {
                        $message .= "<br>Failed to add employee to login table.";
                    }
                } else {
                    $message = "Failed to add employee.";
                }
            }
        } else {
            $message = "Manager not found.";
        }
    } elseif (isset($_POST['remove_employee'])) {
        $emp_id = $_POST['emp_id_remove'];
        $emp_query = "SELECT name FROM employee WHERE emp_id = $1";
        $result = pg_query_params($connection, $emp_query, array($emp_id));
        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            $name = $row['name'];
            $query = "DELETE FROM employee WHERE emp_id = $1";
            $result = pg_query_params($connection, $query, array($emp_id));
            if ($result) {
                $message = "Employee removed successfully";
                $login_connection = pg_connect("host=localhost dbname=login user=postgres password=abi13");
                $delete_login_query = "DELETE FROM login WHERE username = $1";
                $delete_login_result = pg_query_params($login_connection, $delete_login_query, array($name));
                if (!$delete_login_result) {
                    $message .= "<br>Failed to remove employee from login table.";
                }
            } else {
                $message = "Failed to remove employee.";
            }
        } else {
            $message = "Employee does not exist";
        }
    } elseif (isset($_POST['view_customers'])) {
        $manager_query = "SELECT branch_id FROM manager WHERE name = $1";
        $manager_result = pg_query_params($connection, $manager_query, array($username));
        if ($manager_result && pg_num_rows($manager_result) > 0) {
            $manager_row = pg_fetch_assoc($manager_result);
            $branch_id = $manager_row['branch_id'];
            $query = "SELECT cus_name, acc_no, balance, age, gender, address, contact, acc_type FROM customer WHERE branch_id = $1";
            $result = pg_query_params($connection, $query, array($branch_id));
            if ($result && pg_num_rows($result) > 0) {
                while ($row = pg_fetch_assoc($result)) {
                    $customer_details[] = $row;
                }
            } else {
                $message = "No customers found for the specified branch ID.";
            }
        } else {
            $message = "Manager not found.";
        }
    } elseif (isset($_POST['view_employees'])) {
        $manager_query = "SELECT branch_id FROM manager WHERE name = $1";
        $manager_result = pg_query_params($connection, $manager_query, array($username));
        if ($manager_result && pg_num_rows($manager_result) > 0) {
            $manager_row = pg_fetch_assoc($manager_result);
            $branch_id = $manager_row['branch_id'];
            $query = "SELECT emp_id, name, mgr_id, branch_id FROM employee WHERE branch_id = $1";
            $result = pg_query_params($connection, $query, array($branch_id));
            if ($result && pg_num_rows($result) > 0) {
                while ($row = pg_fetch_assoc($result)) {
                    $employee_details[] = $row;
                }
            } else {
                $message = "No employees found for the specified branch ID.";
            }
        } else {
            $message = "Manager not found.";
        }
    }
    elseif (isset($_POST['provide_loan'])) {
        $acc_no_loan = $_POST['acc_no_loan'];
        $amount = $_POST['amount'];
        $interest = $_POST['interest'];
        $duration = $_POST['duration'];
        $manager_query = "SELECT mgr_id FROM manager WHERE name = $1";
        $manager_result = pg_query_params($connection, $manager_query, array($username));
        if ($manager_result && pg_num_rows($manager_result) > 0) {
            $manager_row = pg_fetch_assoc($manager_result);
            $mgr_id = $manager_row['mgr_id'];
            $insert_loan_query = "INSERT INTO loan (acc_no, amount, interest, duration, mgr_id) 
                                  VALUES ($1, $2, $3, $4, $5)";
            $insert_loan_result = pg_query_params($connection, $insert_loan_query, 
                                array($acc_no_loan, $amount, $interest, $duration, $mgr_id));
            if ($insert_loan_result) {
                $message = "Loan provided successfully";
            } else {
                $message = "Failed to provide loan. Invalid details";
            }
        } else {
            $message = "Manager not found";
        }
    } elseif (isset($_POST['view_loans'])) {
        $manager_query = "SELECT mgr_id FROM manager WHERE name = $1";
        $manager_result = pg_query_params($connection, $manager_query, array($username));
        if ($manager_result && pg_num_rows($manager_result) > 0) {
            $manager_row = pg_fetch_assoc($manager_result);
            $mgr_id = $manager_row['mgr_id'];
            $query = "SELECT * FROM loan WHERE mgr_id = $1"; 
            $result = pg_query_params($connection, $query, array($mgr_id));
            if ($result && pg_num_rows($result) > 0) {
                while ($row = pg_fetch_assoc($result)) {
                    $loan_details[] = $row;
                }
            } else {
                $message = "No loans found for the specified manager";
            }
        } else {
            $message = "Manager not found.";
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
    <title>Manager Dashboard</title>
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
#submit-c{
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
    width: 190px;
}
#submit-btn:hover, #submit-btn:focus,
#remove-btn:hover, #remove-btn:focus,
#submit-c:hover, #submit-c:focus {
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
table ,th,td{
  border-collapse: collapse;
  border: 1px solid black;
}
th, td {
  padding: 15px;
  text-align: center;
}
th {
  background-color: #339999;
  color: white;
}
table.center {
  margin-left: auto; 
  margin-right: auto;
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
                    <br><center><h2 class="mu-heading-title">Manager Dashboard</h2></center><br>
                        <div class="mu-service-header">
                            <span class="mu-header-dot"></span><br>
                            <div><?php echo $message; ?></div><br>
                            <h3>Add Employee</h3><hr style="width:50%; border-top: 1px solid black;">
                            <form action="usermgr.php" method="POST">
                            <label for="emp_id">Enter Employee ID:</label><br>
                            <input type="text" id="emp_id" name="emp_id" required><br><br>
                            <label for="name">Enter Name:</label><br>
                            <input type="text" id="name" name="name" required><br><br>
                            <input type="submit" name="add_employee" value="Add" id="submit-btn">
                            </form>
                            <br><br>
                            <h3>Remove Employee</h3><hr style="width:50%; border-top: 1px solid black;">
                            <form action="usermgr.php" method="POST">
                            <label for="emp_id_remove">Enter Employee ID:</label><br>
                            <input type="text" id="emp_id_remove" name="emp_id_remove" required><br><br>
                            <input type="submit" name="remove_employee" value="Remove" id="submit-btn">
                            </form><br><br>
                            <h3>Provide Loan</h3><hr style="width:50%; border-top: 1px solid black;">
                            <form action="usermgr.php" method="POST">
                                <label for="acc_no_loan">Enter Account Number:</label><br>
                                <input type="text" id="acc_no_loan" name="acc_no_loan" required><br><br>
                                <label for="amount">Enter Amount:</label><br>
                                <input type="number" id="amount" name="amount" required><br><br>
                                <label for="interest">Enter Rate of Interest :</label><br>
                                <input type="number" id="interest" name="interest" required><br><br>
                                <label for="duration">Enter Duration (year):</label><br>
                                <input type="number" id="duration" name="duration" required><br><br>
                                <input type="submit" name="provide_loan" value="Provide" id="submit-btn">
                            </form><br><br>
                            <h3>View Details</h3><hr style="width:50%; border-top: 1px solid black;">
                            <form action="usermgr.php" method="POST">
                                <input type="submit" name="view_customers" value="View Customers" id="submit-c">
                                <input type="submit" name="view_employees" value="View Employees" id="submit-c">
                                <input type="submit" name="view_loans" value="View Loans" id="submit-c">
                            </form><br><br>
                            <?php if(!empty($customer_details)): ?>
                            <h3>Customer Details</h3><hr style="width:50%; border-top: 1px solid black;">
                            <table class="center">
                                <tr>
                                    <th>Name</th>
                                    <th>Account Number</th>
                                    <th>Balance</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Address</th>
                                    <th>Contact</th>
                                    <th>Account Type</th>
                                </tr>
                                <?php foreach ($customer_details as $customer): ?>
                                <tr>
                                    <td><?php echo $customer['cus_name']; ?></td>
                                    <td><?php echo $customer['acc_no']; ?></td>
                                    <td><?php echo $customer['balance']; ?></td>
                                    <td><?php echo $customer['age']; ?></td>
                                    <td><?php echo $customer['gender']; ?></td>
                                    <td><?php echo $customer['address']; ?></td>
                                    <td><?php echo $customer['contact']; ?></td>
                                    <td><?php echo $customer['acc_type']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                            <?php endif; ?>
                            <br><br>
                            <?php if(!empty($employee_details)): ?>
                            <h3>Details of Employees</h3><hr style="width:50%; border-top: 1px solid black;">
                            <table class="center">
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Name</th>
                                    <th>Manager ID</th>
                                    <th>Branch ID</th>
                                </tr>
                                <?php foreach ($employee_details as $employee): ?>
                                <tr>
                                    <td><?php echo $employee['emp_id']; ?></td>
                                    <td><?php echo $employee['name']; ?></td>
                                    <td><?php echo $employee['mgr_id']; ?></td>
                                    <td><?php echo $employee['branch_id']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table><br><br>
                            <?php endif; ?>
                            <?php if(!empty($loan_details)): ?>
                            <h3>Loan Details</h3><hr style="width:50%; border-top: 1px solid black;">
                            <table class="center">
                                <tr>
                                    <th>Account Number</th>
                                    <th>Amount</th>
                                    <th>Interest</th>
                                    <th>Duration</th>
                                </tr>
                                <?php foreach ($loan_details as $loan): ?>
                                <tr>
                                    <td><?php echo $loan['acc_no']; ?></td>
                                    <td><?php echo $loan['amount']; ?></td>
                                    <td><?php echo $loan['interest']; ?></td>
                                    <td><?php echo $loan['duration']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                            <?php endif; ?><br><br>
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
