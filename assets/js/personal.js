document.addEventListener('DOMContentLoaded', () => {
    const avatarForm = document.querySelector('#avatar-form');
    const profileForm = document.querySelector('#profile-form');
    const avatarInput = document.querySelector('#avatar-input');
    const countrySelect = document.querySelector('#country-code');
    const citySelect = document.querySelector('#city');
    const avatarImg = document.querySelector('#avatar-img');
    const avatarError = document.querySelector('#avatar-error');
    const profileError = document.querySelector('#profile-error');
    const friendActionBtn = document.querySelector('#friend-action-btn');

    // Country-City dynamic update
    if (countrySelect && citySelect) {
        const cityOptions = {
            '台灣': ['台北', '其他'],
            '美國': ['紐約', '其他'],
            '日本': ['東京', '其他']
        };
        const currentCity = citySelect.value;

        countrySelect.addEventListener('change', () => {
            const country = countrySelect.value;
            citySelect.innerHTML = '<option value="">請選擇城市</option>';
            if (cityOptions[country]) {
                cityOptions[country].forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    if (city === currentCity) {
                        option.selected = true;
                    }
                    citySelect.appendChild(option);
                });
            }
        });

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
                const response = await fetch('/api/backend', {
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
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showError(profileError, result.message);
                }
            } catch (error) {
                showError(profileError, '更新失敗，請稍後再試');
            }
        });
    }

    // Check friend status and update button
    if (friendActionBtn) {
        const friendId = friendActionBtn.dataset.friendId;
        checkFriendStatus(friendId);
    }

    // Load friends and pending requests
    loadFriends();
    loadPendingRequests();

    // Add friend
    window.addFriend = function (friendId) {
        fetch('/api/backend', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add_friend', friend_id: friendId, csrf_token: '<?php echo htmlspecialchars($csrf_token); ?>' })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    checkFriendStatus(friendId);
                } else {
                    showError(profileError, data.message);
                }
            })
            .catch(error => {
                checkFriendStatus(friendId); // 確保按鈕狀態更新
            });
    };

    // Delete friend
    window.deleteFriend = function (friendId) {
        if (!confirm('確定要刪除此好友嗎？')) return;

        fetch('/api/backend', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_friend', friend_id: friendId, csrf_token: '<?php echo htmlspecialchars($csrf_token); ?>' })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showSuccess(data.message);
                    loadFriends();
                    if (friendActionBtn && friendActionBtn.dataset.friendId == friendId) {
                        checkFriendStatus(friendId);
                    }
                } else {
                    showError(profileError, data.message);
                }
            })
            .catch(error => {
                showError(profileError, '刪除好友失敗，請稍後再試');
            });
    };

    // Respond to friend request
    window.respondFriendRequest = function (requestId, response) {
        fetch('/api/backend', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'respond_friend_request', request_id: requestId, response, csrf_token: '<?php echo htmlspecialchars($csrf_token); ?>' })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showSuccess(data.message);
                    loadPendingRequests();
                    loadFriends();
                } else {
                    showError(profileError, data.message);
                }
            })
            .catch(error => {
                showError(profileError, '處理請求失敗，請稍後再試');
            });
    };
    window.respondFriendRequest = function (requestId, response) {
        fetch('/api/backend', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'respond_friend_request', request_id: requestId, response, csrf_token: '<?php echo htmlspecialchars($csrf_token); ?>' })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showSuccess(data.message);
                    loadPendingRequests();
                    loadFriends();
                } else {
                    showError(profileError, data.message);
                }
            })
            .catch(error => {
                showError(profileError, '處理請求失敗，請稍後再試');
            });
    };
    // Check friend status
    function checkFriendStatus(friendId) {
        fetch('/api/backend', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_friend_status', friend_id: friendId, csrf_token: '<?php echo htmlspecialchars($csrf_token); ?>' })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && friendActionBtn) {
                    friendActionBtn.dataset.friendId = friendId;
                    if (data.friend_status === 'pending') {
                        friendActionBtn.textContent = '好友請求待處理';
                        friendActionBtn.disabled = true;
                        friendActionBtn.onclick = null;
                    } else if (data.friend_status === 'accepted') {
                        friendActionBtn.textContent = '刪除好友';
                        friendActionBtn.className = 'btn btn-danger btn-sm';
                        friendActionBtn.disabled = false;
                        friendActionBtn.onclick = () => deleteFriend(friendId);
                    } else if (data.friend_status === 'rejected') {
                        friendActionBtn.textContent = '重新添加好友';
                        friendActionBtn.className = 'btn btn-primary btn-sm';
                        friendActionBtn.disabled = false;
                        friendActionBtn.onclick = () => addFriend(friendId);
                    } else {
                        friendActionBtn.textContent = '添加好友';
                        friendActionBtn.className = 'btn btn-primary btn-sm';
                        friendActionBtn.disabled = false;
                        friendActionBtn.onclick = () => addFriend(friendId);
                    }
                }
            })
            .catch(error => {
                showError(profileError, '無法檢查好友狀態，請稍後再試');
            });
    }

    // Load friends
    function loadFriends() {
        const friendsList = document.getElementById('friends-list');
        if (!friendsList) return;

        fetch('/api/backend', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_friends', csrf_token: '<?php echo htmlspecialchars($csrf_token); ?>' })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.friends.length > 0) {
                    let html = '<ul class="list-group">';
                    data.friends.forEach(friend => {
                        html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="friend-link" data-username="${encodeURIComponent(friend.username)}">${friend.username}</span>
                        <button class="btn btn-danger btn-sm" onclick="deleteFriend(${friend.user_id})">刪除好友</button>
                    </li>`;
                    });
                    html += '</ul>';
                    friendsList.innerHTML = html;

                    // 添加點擊事件處理
                    document.querySelectorAll('.friend-link').forEach(link => {
                        link.addEventListener('click', (e) => {
                            e.preventDefault();
                            const username = decodeURIComponent(link.dataset.username);
                            fetch('/personal', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `username=${encodeURIComponent(username)}&csrf_token=<?php echo htmlspecialchars($csrf_token); ?>`
                            })
                                .then(response => response.text())
                                .then(html => {
                                    document.open();
                                    document.write(html);
                                    document.close();
                                })
                                .catch(error => {
                                    showError(profileError, '跳轉個人主頁失敗，請稍後再試');
                                });
                        });
                    });
                } else {
                    friendsList.innerHTML = '<p>暫無好友</p>';
                }
            })
            .catch(error => {
                showError(profileError, '載入好友列表失敗，請稍後再試');
            });
    }

    // Load pending friend requests
    function loadPendingRequests() {
        const pendingRequests = document.getElementById('pending-requests');
        if (!pendingRequests) return;

        fetch('/api/backend', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_pending_requests', csrf_token: '<?php echo htmlspecialchars($csrf_token); ?>' })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.requests.length > 0) {
                    let html = '<ul class="list-group">';
                    data.requests.forEach(request => {
                        html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                        ${request.username} 請求添加你為好友
                        <div>
                            <button class="btn btn-sm btn-success" onclick="respondFriendRequest(${request.request_id}, 'accept')">接受</button>
                            <button class="btn btn-sm btn-danger" onclick="respondFriendRequest(${request.request_id}, 'reject')">拒絕</button>
                        </div>
                    </li>`;
                    });
                    html += '</ul>';
                    pendingRequests.innerHTML = html;
                } else {
                    pendingRequests.innerHTML = '<p>暫無待處理的好友請求</p>';
                }
            })
            .catch(error => {
                showError(profileError, '載入好友請求失敗，請稍後再試');
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
        alert(message);
    }
});