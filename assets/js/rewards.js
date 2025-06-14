function redeemReward(rewardId) {
    fetch('/api/backend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'redeem', reward_id: rewardId })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json(); // 直接解析 JSON
    })
    .then(data => {
        console.log('兌換請求回應:', data);
        if (data.status === 'success') {
            alert(data.message);
            window.location.reload(); // 重新載入頁面更新獎勵列表
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('兌換失敗:', error);
        alert(`兌換失敗：${error.message}`);
    });
}