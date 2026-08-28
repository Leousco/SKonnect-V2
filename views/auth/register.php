<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKonnect | Register</title>

    <link rel="stylesheet" href="../../styles/global.css">
    <link rel="stylesheet" href="../../styles/auth/login.css">
    <link rel="stylesheet" href="../../styles/auth/register.css">
</head>
<body>

<nav id="navbar">
    <div class="navbar-container">
        <a href="../public/main.php" class="navbar-logo">
            <img src="../../assets/img/loger.jpg" alt="SK Logo">
            <span>SKonnect</span>
        </a>
        <ul class="navbar-menu" id="navbarMenu">
            <li><a href="../public/main.php" class="nav-link"><i class="fa-solid fa-arrow-left"></i> Home</a></li>
        </ul>
    </div>
</nav>

<main class="hero">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>

    <div class="login-card register-card">

        <div class="card-logo">
            <img src="../../assets/img/loger.jpg" alt="SK Logo">
        </div>

        <h1 class="card-title">REGISTER</h1>
        <p class="card-subtitle">Create your SKonnect account</p>

        <form action="../../backend/routes/auth.php" method="POST" id="registerForm" autocomplete="off">

            <input type="hidden" name="action" value="register">

            <!-- Full name -->
            <div class="form-row three-col">
                <div class="input-group">
                    <label for="first_name">First Name <span class="req">*</span></label>
                    <input type="text" id="first_name" name="first_name" class="input-field" placeholder="First name" autocomplete="off" required>
                </div>
                <div class="input-group">
                    <label for="last_name">Last Name <span class="req">*</span></label>
                    <input type="text" id="last_name" name="last_name" class="input-field" placeholder="Last name" autocomplete="off" required>
                </div>
                <div class="input-group">
                    <label for="middle_name">Middle Name <span class="opt">(optional)</span></label>
                    <input type="text" id="middle_name" name="middle_name" class="input-field" placeholder="Middle name" autocomplete="off">
                </div>
            </div>

            <!-- Gender, Birth Date, Age -->
            <div class="form-row three-col">
                <div class="input-group">
                    <label for="gender">Gender <span class="req">*</span></label>
                    <select id="gender" name="gender" class="input-field select-field" autocomplete="off" required>
                        <option value="" disabled selected>Select</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="birth_date">Birth Date <span class="req">*</span></label>
                    <input type="date" id="birth_date" name="birth_date" class="input-field" autocomplete="off" required>
                </div>

                <div class="input-group">
                    <label>Age</label>
                    <div class="age-display">
                        <span id="ageValue">—</span>
                        <span class="age-unit" id="ageUnit"></span>
                    </div>

                    <input type="hidden" id="age" name="age">
                </div>
                
            </div>

            <!-- Email -->
            <div class="form-row one-col">
                <div class="input-group">
                    <label for="email">Email Address <span class="req">*</span></label>
                    <input type="email" id="email" name="email" class="input-field" placeholder="Enter your email address" autocomplete="off" required>
                </div>
            </div>

            <!-- Password, Confirm Password -->
            <div class="form-row two-col">
                <div class="input-group">
                    <label for="password">Password <span class="req">*</span></label>
                    <div class="password-wrap">
                        <input type="password" id="password" name="password" class="input-field" placeholder="Create password"
                               autocomplete="new-password" required>
                        <button type="button" class="toggle-pw" data-target="password" aria-label="Toggle password">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="input-group">
                    <label for="confirm_password">Confirm Password <span class="req">*</span></label>
                    <div class="password-wrap">
                        <input type="password" id="confirm_password" name="confirm_password" class="input-field" placeholder="Repeat password"
                               autocomplete="new-password" required>
                        <button type="button" class="toggle-pw" data-target="confirm_password" aria-label="Toggle password">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <span class="pw-match-msg" id="pwMatchMsg"></span>
                </div>
            </div>

            <!-- Privacy Policy Checkbox -->
            <div class="form-row one-col">
                <div class="privacy-policy-checkbox">
                    <label class="checkbox-container">
                        <input type="checkbox" id="privacyCheckbox" name="agree_to_terms" required>
                        <span class="checkmark"></span>
                        <span class="checkbox-text">
                            I have read and accept the 
                            <a href="../public/privacy_policy.php" class="policy-link">Data Privacy Policy</a> 
                            <span class="required-indicator">*</span>
                        </span>
                    </label>
                </div>
            </div>

            <button type="submit" class="login-btn">Verify Account</button>

            <p class="register-block">
                Already have an account? <a href="login.php" class="register-link">Login</a>
            </p>

        </form>
    </div>
</main>

<script src="../../scripts/auth/register.js"></script>

</body>
</html>