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
	if(!$message)
	{
		$_SESSION['messsage'] = $messsage;
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
				echo "<h3><center>$error</center></h3>";
			}
		}
	}
}




 ?>
