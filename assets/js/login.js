document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    // 處理登入表單
    if (loginForm) {
        loginForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const errorContainer = document.getElementById('error-container');
            errorContainer.innerHTML = ''; // 清除舊錯誤
            const formData = new FormData(loginForm);
            fetch('/api/backend.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status == 'success') {
                    window.location.href = '/tracking';
                } else {
                    window.location.href = '/';
                    alert(`登入失敗：${data.error || '未知錯誤'}`);
                }
            })
            .catch(error => {
                alert('登入失敗：' + error.message);
                window.location.href = '/';
            });
        });
    }

    // 處理註冊表單
    if (registerForm) {
        registerForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const errorContainer = document.getElementById('error-container');
            errorContainer.innerHTML = ''; // 清除舊錯誤
            const formData = new FormData(registerForm);
            fetch('/api/backend.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = '/';
                } else {
                    alert(`註冊失敗：${data.error || '未知錯誤'}`);
                    window.location.href = '/register';
                }
            })
            .catch(error => {
                alert('註冊失敗：' + error.message);
                window.location.href = '/register';
            });
        });
    }
});