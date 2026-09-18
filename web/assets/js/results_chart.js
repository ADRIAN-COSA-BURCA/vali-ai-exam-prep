const ctx = document.getElementById('scoreChart').getContext('2d');
const score = parseInt(document.querySelector('.score-label-center h2').innerText);

new Chart(ctx, {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [score, 100 - score],
            backgroundColor: ['#06b6d4', 'rgba(255,255,255,0.1)'],
            borderWidth: 0
        }]
    },
    options: {
        cutout: '75%',
        plugins: { legend: { display: false } }
    }
});
