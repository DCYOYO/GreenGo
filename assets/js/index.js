window.onload = () => {
  showLoginForm();
};

function showLoginForm() {
  document.getElementById('login-form').classList.remove('d-none');
  document.getElementById('register-form').classList.add('d-none');
}

function showRegisterForm() {
  document.getElementById('login-form').classList.add('d-none');
  document.getElementById('register-form').classList.remove('d-none');
}

function login() {
  const username = document.getElementById('login-username').value;
  const password = document.getElementById('login-password').value;
  if (!username || !password) {
    showError('login-error', '請輸入使用者名稱和密碼');
    return;
  }
  fetch('api/backend.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'login', username, password })
  })
  .then(response => {
    console.log("登入 - HTTP 狀態碼：", response.status);
    return response.json();
  })
  .then(data => {
    console.log("登入 - 後端回應：", data);
    if (data.status === 'success') {
      window.location.href = 'pages/tracking.php';
    } else {
      showError('login-error', data.message);
    }
  })
  .catch(error => {
    console.error('登入失敗:', error);
    showError('login-error', '無法連接到後端，請檢查伺服器是否運行');
  });
}

function register() {
  const username = document.getElementById('register-username').value;
  const password = document.getElementById('register-password').value;
  const confirmPassword = document.getElementById('register-password-confirm').value;
  if (!username || !password || !confirmPassword) {
    showError('register-error', '請填寫所有欄位');
    return;
  }
  if (password !== confirmPassword) {
    showError('register-error', '密碼不一致');
    return;
  }
  if (username.length < 3) {
    showError('register-error', '使用者名稱需至少3個字元');
    return;
  }
  fetch('api/backend.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'register', username, password })
  })
  .then(response => {
    console.log("註冊 - HTTP 狀態碼：", response.status);
    return response.json();
  })
  .then(data => {
    console.log("註冊 - 後端回應：", data);
    if (data.status === 'success') {
      alert('註冊成功，請登入');
      showLoginForm();
    } else {
      showError('register-error', data.message);
    }
  })
  .catch(error => {
    console.error('註冊失敗:', error);
    showError('register-error', '無法連接到後端，請檢查伺服器是否運行');
  });
}