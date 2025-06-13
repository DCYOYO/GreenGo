document.addEventListener('DOMContentLoaded', () => {
    const avatarForm = document.querySelector('#avatar-form');
    const profileForm = document.querySelector('#profile-form');
    const avatarInput = document.querySelector('#avatar-input');
    const countrySelect = document.querySelector('#country-code');
    const citySelect = document.querySelector('#city');
    const avatarImg = document.querySelector('#avatar-img');
    const avatarError = document.querySelector('#avatar-error');
    const profileError = document.querySelector('#profile-error');
    const showAvatarFormBtn = document.querySelector('#show-avatar-form-btn');
    const showProfileFormBtn = document.querySelector('#show-profile-form-btn');

    // 顯示上傳頭像表單
    showAvatarFormBtn.addEventListener('click', () => {
        avatarForm.classList.toggle('hidden-form');
        if (!avatarForm.classList.contains('hidden-form')) {
            avatarInput.focus();
        }
    });

    // 顯示編輯個人資料表單
    showProfileFormBtn.addEventListener('click', () => {
        profileForm.classList.toggle('hidden-form');
        if (!profileForm.classList.contains('hidden-form')) {
            countrySelect.focus();
        }
    });
    // Country-City dynamic update
    if (countrySelect && citySelect) {
        const cityOptions = {
            '台灣': ['台北', '其他'],
            '美國': ['紐約', '其他'],
            '日本': ['東京', '其他']
        };
        const currentCity = citySelect.value; // Get the initially selected city from HTML

        countrySelect.addEventListener('change', () => {
            const country = countrySelect.value;
            citySelect.innerHTML = '<option value="">請選擇城市</option>';
            if (cityOptions[country]) {
                cityOptions[country].forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    // Pre-select if it matches the current city
                    if (city === currentCity) {
                        option.selected = true;
                    }
                    citySelect.appendChild(option);
                });
            }
        });

        // Trigger initial update if a country is pre-selected
        if (countrySelect.value) {
            countrySelect.dispatchEvent(new Event('change'));
        }
    }

    // Handle avatar upload
    if (avatarForm) {
        avatarForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!avatarInput.files[0]) {
                showError(avatarError, '請選擇圖像文件');
                return;
            }

            const formData = new FormData(avatarForm);
            try {
                const response = await fetch('/api/backend.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    if (avatarImg && result.avatar) {
                        avatarImg.src = result.avatar + '?t=' + new Date().getTime();
                    }
                    showSuccess(result.message);
                } else {
                    showError(avatarError, result.message);
                }
            } catch (error) {
                showError(avatarError, '上傳失敗，請稍後再試');
            }
        });
    }

    // Handle profile update
    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(profileForm);
            try {
                const response = await fetch('/api/backend', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    showSuccess(result.message);
                    setTimeout(() => location.reload());
                } else {
                    showError(profileError, result.message);
                }
            } catch (error) {
                showError(profileError, '更新失敗，請稍後再試');
            }
        });
    }

    function showError(element, message) {
        if (element) {
            element.textContent = message;
            element.style.display = 'block';
            setTimeout(() => element.style.display = 'none', 5000);
        } else {
            alert(message);
        }
    }

    function showSuccess(message) {
        alert(message); // Replace with a better UI notification if needed
    }
});