<?php

include("functions/init.php");
session_destroy();
redirect("login.php");

if(isset($_COOKIE['email']))
{
  unset($_COOKIE['email']);
  setcookie('email','',time()-86400);
}

 ?>
