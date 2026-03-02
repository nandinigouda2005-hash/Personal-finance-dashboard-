<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register & Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
 <div class="container" id="sign in">
    <h1 class="form-title">Sign In</h1>
    <form method="post" action="login_process.php">
       
        <div class="input-group">
            <i class="fas fa-envelope"></i> 
             <input type="email" name="email" id="email" placeholder="E-mail" required>
            <label for="email">E-mail</label>
        </div>
        <div class="input-field">
           <i class="fas fa-lock"></i>
            <input type="password" name="password" id="password" placeholder="Password" required>
            <label for="password">Password</label> 
        </div>
        <p class="recover">
            <a href="#">Recover password</a>
        </p>
        <input type="submit" class="btn" value="Sign in" name="Sign in">
    </form>
    <p class="or">
        -------or-------
    </p>
    <div class="icons">
        <i class="fab fa-google"></i>
        <i class="fab fa-facebook"></i>
    </div>
    <div class="links">
        <p>Don't have account?</p>
        <button id="signupbutton">Sign up</button>
    </div>
   </div>

 <div class="container" id="sign up" style="display: none;">
    <h1 class="form-title">Register</h1>
    <form method="post" action="">
        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="first name" id="first name" placeholder="First Name" required>
        <label for="first name">First Name</label>
        </div>
        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="last name" id="last name" placeholder="Last Name" required>
            <label for="last name">Last Name</label>
        </div>
        <div class="input-group">
            <i class="fas fa-envelope"></i> 
             <input type="email" name="email" id="email" placeholder="E-mail" required>
            <label for="email">E-mail</label>
        </div>
        <div class="input-field">
           <i class="fas fa-lock"></i>
            <input type="password" name="password" id="password" placeholder="Password" required>
            <label for="password">Password</label> 
        </div>
        <input type="submit" class="btn" value="Sign up" name="Sign up">
    </form>
    <p class="or">
        -------or-------
    </p>
    <div class="icons">
        <i class="fab fa-google"></i>
        <i class="fab fa-facebook"></i>
    </div>
    <div class="links">
        <p>Already have account?</p>
        <button id="signinbutton">Sign in</button>
    </div>
   </div>
   
   <script src="script.js"></script>
</body>
</html>