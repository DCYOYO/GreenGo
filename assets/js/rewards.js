function redeemReward(rewardId) {
    fetch('/api/backend.php', {
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
        //loadRewards();
      } else {
        alert(data.message);
      }
    })
    .catch(error => console.error('兌換失敗:', error));
  }