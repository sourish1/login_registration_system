<?php

function clean($string)
{
	return htmlentities($string);
}


function redirect($location)
{
	header("Location: $location");
}

function set_message($message)
{
	if($message)
	{
		$_SESSION['message'] = $message;
	}else{
		$messsage = "";
	}
}


function display_message()
{
	if(isset($_SESSION['message']))
	{
		echo $_SESSION['message'];
		unset($_SESSION['message']);
	}
}


function token_generator()
{
	$token = md5(uniqid(mt_rand(), true));
	$_SESSION['token'] = $token;
	return $token;
}


function email_exists($email)
{
	$sql = "SELECT * FROM users WHERE email = '$email'";
	$result = query($sql);
	if(row_count($result) == 1)
	{
		return true;
	}else {
		return false;
	}
}

function username_exists($username)
{
	$sql = "SELECT * FROM users WHERE username = '$username'";
	$result = query($sql);
	if(row_count($result) == 1)
	{
		return true;
	}else {
		return false;
	}
}


function validation_errors($error_message){
    $alert_error_message = "
    <div class='alert alert-danger alert-dismissible' role='alert'>
    <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
    <strong>Warning!</strong>
    {$error_message}
    </div>
    ";
    return $alert_error_message;
}


function send_email($email, $subject, $msg, $headers)
{
	return mail($email, $subject, $msg, $headers);
}


function validate_user_registration()
{
	$errors = [];
	$min = 3;
	$max = 20;

	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		$first_name = clean($_POST['first_name']);
		$last_name = clean($_POST['last_name']);
		$username = clean($_POST['username']);
		$email = clean($_POST['email']);
		$password = clean($_POST['password']);
		$confirm_password = clean($_POST['confirm_password']);

		if(strlen($first_name) < $min)
		{
			$errors[] = "Your first name cannot be less than $min characters";
		}

		if(strlen($first_name) > $max)
		{
			$errors[] = "Your first name cannot be more than $max characters";
		}

		if(strlen($last_name) < $min)
		{
			$errors[] = "Your last name cannot be less than $min characters";
		}

		if(strlen($last_name) > $max)
		{
			$errors[] = "Your last name cannot be more than $max characters";
		}

		if(strlen($username) < $min)
		{
			$errors[] = "Your username cannot be less than $min characters";
		}

		if(strlen($last_name) > $max)
		{
			$errors[] = "Your username cannot be more than $max characters";
		}

		if($password != $confirm_password)
		{
			$errors[] = "Your password fields donot match";
		}

		if(email_exists($email))
		{
			$errors[] = "Email already exists";
		}

		if(username_exists($username))
		{
			$errors[] = "username already exists";
		}

		if(!empty($errors))
		{
			foreach($errors as $error)
			{
				// echo "<h3><center>$error</center></h3>";
				echo validation_errors($error);
			}
		}else {
			if(register_user($first_name, $last_name, $username, $email, $password))
			{
				set_message("<p class='bg-success text-center'>Please check your email or spam folder for activation link</p>");
				redirect("index.php");
				//echo "<h3><center>user registration successful</center></h3>";
			}else {
				set_message("<p class='bg-danger text-center'>Sorry. We cannot register the user</p>");
				redirect("index.php");
			}
		}
	}
}

function register_user($first_name, $last_name, $username, $email, $password)
{
	$first_name = escape($first_name);
	$last_name = escape($last_name);
	$email = escape($email);
	$password = escape($password);


	if(email_exists($email))
	{
		return false;
	}else if(username_exists($username)) {
		return false;
	}else {
		$password = md5($password);
		$validation_code = md5($username + microtime());
		$sql = "INSERT INTO users(first_name,last_name,username,email,password,validation_code) VALUES('$first_name','$last_name','$username','$email','$password','$validation_code')";
		$result = query($sql);
		confirm($result);

		$subject = "Activation account";
		$msg = "Please click the link to activate your account http://127.0.0.1/login/activate.php?email=$email&code=$validation_code";
		$headers = "From: noreply@mywebsite.com";
		send_email($email, $subject, $msg, $headers);

		return true;
	}
}

function activate_user()
{
	if($_SERVER['REQUEST_METHOD'] == "GET")
	{
		if(isset($_GET['email']))
		{
			$email = clean($_GET['email']);
			$email = escape($_GET['email']);
		  $validation_code = clean($_GET['code']);
			$validation_code = escape($_GET['code']);
			$sql = "SELECT * FROM users WHERE email='$email' AND validation_code='$validation_code'";
			$result = query($sql);
			confirm($result);
			if(row_count($result) == 1)
			{
				$sql2 = "UPDATE users SET active=1, validation_code=0 WHERE email='$email' AND validation_code='$validation_code'";
				$result2 = query($sql2);
				confirm($result2);
				set_message("<p class='bg-success'>Your account has been activated. Please login</p>");
				redirect("login.php");

			}else {
				set_message("<p class='bg-danger'>Your account cannot be activated</p>");
				redirect("login.php");
			}
		}
	}
}



function validate_user_login()
{
	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		$email = clean($_POST['email']);
		$password = clean($_POST['password']);
		$remember = isset($_POST['remember']);

		if(empty($email))
		{
			$errors[] = "Email field cannot be empty";
		}

		if(empty($password))
		{
			$errors[] = "Password field cannot be empty";
		}

		if(!empty($errors))
		{
			foreach($errors as $error)
			{
				// echo "<h3><center>$error</center></h3>";
				echo validation_errors($error);
			}
		}else {
			if(login_user($email,$password,$remember))
			{
				redirect("admin.php");
			}else {
				echo validation_errors("Your credentials are incorrect");
			}
		}
	}


}


function login_user($email,$password,$remember)
{
	$email = escape($email);
	$sql = "SELECT * FROM users WHERE email='$email' AND active=1";
	$result = query($sql);
	if(row_count($result) == 1)
	{
		$row = fetch_array($result);
		$db_password = $row['password'];
		if(md5($password) == $db_password)
		{
			if($remember == "on")
			{
				setcookie('email', $email, time() + 86400);
			}
			$_SESSION['email'] = $email;
			return true;
		}else {
			return false;
		}

		return true;

	}else {
		return false;
	}
}


function logged_in()
{
	if(isset($_SESSION['email']) || isset($_COOKIE['email']))
	{
		return true;
	}else {
		return false;
	}
}


function recover_password()
{
	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		if(isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token'])
		{
			$email = clean($_POST['email']);
			if(email_exists($email))
			{
				$validation_code = md5($email + microtime());
				setcookie('temp_access_code', $validation_code, time()+ 900);
				$validation_code = escape($validation_code);
				$email = escape($email);
				$sql = "UPDATE users SET validation_code='$validation_code' WHERE email='$email'";
				$result = query($sql);
				confirm($result);
				$subject = "Please reset your password";
				$message = "Here is your password reset code $validation_code
				Click here to reset your password http://127.0.0.1/login/code.php?email=$email&code=$validation_code";
				$headers = "From: noreply@mywebsite.com";
				if(!send_email($email, $subject, $message, $headers))
				{
					echo validation_errors("email cannot be sent");
				}

				set_message("<p class='bg-success text-center'>Please check the email or spam folder for a password reset code</p>");
				redirect("index.php");

			}else {
				echo validation_errors("This email doesnot exist");
			}
		}else {
			redirect("index.php");
		}

		if(isset($_POST['cancel_submit']))
		{
			redirect("login.php");
		}

	}
}


function validate_code()
{
	if(isset($_COOKIE['temp_access_code']))
	{
		if(!isset($_GET['email']) && !isset($_GET['code']))
			{
				redirect("index.php");
			}else if(empty($_GET['email']) || empty($_GET['code']))
			{
				redirect("index.php");
			}else {
				if(isset($_POST['code']))
				{
					$email = clean($_GET['email']);
					$email = escape($email);
					$validation_code = clean($_POST['code']);
					$validation_code = escape($validation_code);
					$sql = "SELECT * FROM users WHERE validation_code='$validation_code' AND email='$email'";
					$result = query($sql);
					confirm($result);
					if(row_count($result) == 1)
					{
						setcookie('temp_access_code', $validation_code, time()+ 300);
						redirect("reset.php?email=$email&code=$validation_code");
					}else {
						echo validation_errors("Sorry! Wrong validation code");
					}

				}
			}

	}else {
		set_message("<p class='bg-danger text-center'>Sorry. Your validation code has expired</p>");
		redirect("recover.php");
	}
}


function password_reset()
{
	if(isset($_COOKIE['temp_access_code']))
	{
		if(isset($_GET['email']) && isset($_GET['code']))
			{
				if(isset($_SESSION['token']) && isset($_POST['token']) && $_POST['token'] === $_SESSION['token'])
				{
					if($_POST['password'] === $_POST['confirm_password'])
					{
					$password = escape($_POST['password']);
					$updated_password = md5($password);
					$email = clean($_GET['email']);
					$email = escape($email);
					$sql = "UPDATE users SET password='$updated_password', validation_code=0 WHERE email='$email'";
					$result = query($sql);
					confirm($result);
					set_message("<p class='bg-success text-center'>Your password has been updated. Please login</p>");
					redirect("login.php");
				}else {
					echo validation_errors("Password fields donot match");
				}
				}
			}
	}else {
		set_message("<p class='bg-danger text-center'>Sorry. Your time has expired</p>");
		redirect("recover.php");
	}
}



 ?>
