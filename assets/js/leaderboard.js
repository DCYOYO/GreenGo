window.onload = () => {
  checkLoginStatus(() => {
    loadLeaderboard();
    document.getElementById('leaderboard-section').classList.remove('d-none');
  });
};

function loadLeaderboard() {
  fetch('../api/backend.php?action=leaderboard', { method: 'GET' })
    .then(response => {
      console.log("載入排行榜 - HTTP 狀態碼：", response.status);
      return response.json();
    })
    .then(leaderboard => {
      console.log("載入排行榜 - 後端回應：", leaderboard);
      const list = document.getElementById("leaderboard-list");
      list.innerHTML = "";
      leaderboard.forEach((user, index) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${index + 1}</td>
          <td>${user.username}</td>
          <td>${user.total_points}</td>
          <td>${user.total_footprint.toFixed(2)}</td>
        `;
        list.appendChild(tr);
      });
    })
    .catch(error => console.error('載入排行榜失敗:', error));
}