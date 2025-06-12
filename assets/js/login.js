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
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text(); // 獲取原始文字
            })
            .then(text => {
                console.log('Raw response:', text); // 調試用
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (e) {
                    console.error('JSON parse error:', e);
                    //errorContainer.innerHTML = '<p>回應格式錯誤，請聯繫管理員</p>';
                    window.location.href = '/';
                    return;
                }
                if (data.status === 'success') {
                    alert(data.message);
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.href = '/tracking'; // 後備重定向
                    }
                } else {
                    alert(`${data.message || '未知錯誤'}`);
                    window.location.href = '/';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert(`登入失敗：${error.message}`);
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
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text(); // 獲取原始文字
            })
            .then(text => {
                console.log('Raw response:', text); // 調試用
                let data;
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (e) {
                    console.error('JSON parse error:', e);
                    errorContainer.innerHTML = '<p>回應格式錯誤，請聯繫管理員</p>';
                    return;
                }
                if (data.status === 'success') {
                    alert(data.message);
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.href = '/'; // 後備重定向
                    }
                } else {
                    errorContainer.innerHTML = `<p>${data.message || '未知錯誤'}</p>`;
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                errorContainer.innerHTML = `<p>註冊失敗：${error.message}</p>`;
            });
        });
    }
});