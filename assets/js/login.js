window.onload = () => {
    showLoginForm();
    //checkLoginStatus();
};

function showLoginForm() {
    document.getElementById('login-form').classList.remove('d-none');
    document.getElementById('register-form').classList.add('d-none');
}

function showRegisterForm() {
    document.getElementById('login-form').classList.add('d-none');
    document.getElementById('register-form').classList.remove('d-none');
}

// 移除 login 和 register 函數，因表單已直接提交