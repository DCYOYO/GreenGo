window.onload = () => {
  checkLoginStatus(() => {
    loadRewards();
    document.getElementById('rewards-section').classList.remove('d-none');
  });
};

function loadRewards() {
  fetch('../api/backend.php?action=rewards', { method: 'GET' })
    .then(response => {
      console.log("載入獎勵 - HTTP 狀態碼：", response.status);
      return response.json();
    })
    .then(records => {
      console.log("載入獎勵 - 後端回應：", records);
      const list = document.getElementById("rewards-list");
      list.innerHTML = "";
      records.forEach(reward => {
        const div = document.createElement("div");
        div.className = "card mb-2";
        div.innerHTML = `
          <div class="card-body">
            <h6 class="card-title">${reward.name}</h6>
            <p class="card-text">需要 ${reward.points_required} 點</p>
            <p class="card-text">${reward.description}</p>
            <button class="btn btn-primary" onclick="redeemReward(${reward.id})">兌換</button>
          </div>
        `;
        list.appendChild(div);
      });
    })
    .catch(error => console.error('載入獎勵失敗:', error));
}

function redeemReward(rewardId) {
  fetch('../api/backend.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'redeem', reward_id: rewardId })
  })
  .then(response => {
    console.log("兌換獎勵 - HTTP 狀態碼：", response.status);
    return response.json();
  })
  .then(data => {
    console.log("兌換獎勵 - 後端回應：", data);
    if (data.status === 'success') {
      alert(data.message);
      loadRewards();
    } else {
      alert(data.message);
    }
  })
  .catch(error => console.error('兌換失敗:', error));
}