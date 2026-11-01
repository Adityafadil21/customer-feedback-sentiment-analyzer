const font = { family: "'DM Sans', sans-serif" };
Chart.defaults.font = font;
Chart.defaults.color = '#8992a7';
Chart.defaults.plugins.legend.display = false;
Chart.defaults.plugins.tooltip.backgroundColor = '#171d2b';
Chart.defaults.plugins.tooltip.padding = 12;
Chart.defaults.plugins.tooltip.cornerRadius = 10;

const colors = { positive: '#36c99a', negative: '#f07178', neutral: '#f4bd5e' };
const donut = document.getElementById('sentimentChart');
if (donut) new Chart(donut, {
  type: 'doughnut',
  data: { labels: ['Positive','Negative','Neutral'], datasets: [{ data: sentimentData, backgroundColor: [colors.positive,colors.negative,colors.neutral], borderWidth: 0, hoverOffset: 5 }] },
  options: { cutout: '76%', responsive: true, maintainAspectRatio: false }
});
const trend = document.getElementById('trendChart');
if (trend) new Chart(trend, {
  type: 'line',
  data: { labels: trendLabels, datasets: [
    { label:'Positive', data:trendData.map(x=>x.Positive), borderColor:colors.positive, backgroundColor:colors.positive, tension:.38, pointRadius:3, borderWidth:2 },
    { label:'Negative', data:trendData.map(x=>x.Negative), borderColor:colors.negative, backgroundColor:colors.negative, tension:.38, pointRadius:3, borderWidth:2 },
    { label:'Neutral', data:trendData.map(x=>x.Neutral), borderColor:colors.neutral, backgroundColor:colors.neutral, tension:.38, pointRadius:3, borderWidth:2 }
  ]},
  options: { responsive:true, maintainAspectRatio:false, interaction:{mode:'index',intersect:false}, scales:{ x:{grid:{display:false},border:{display:false}}, y:{beginAtZero:true,ticks:{precision:0},grid:{color:'rgba(145,155,178,.10)'},border:{display:false}} } }
});
const category = document.getElementById('categoryChart');
if (category) new Chart(category, {
  type: 'bar',
  data: { labels:categoryLabels, datasets:[
    {label:'Positive',data:categoryData.map(x=>x.Positive),backgroundColor:colors.positive,borderRadius:4},
    {label:'Negative',data:categoryData.map(x=>x.Negative),backgroundColor:colors.negative,borderRadius:4},
    {label:'Neutral',data:categoryData.map(x=>x.Neutral),backgroundColor:colors.neutral,borderRadius:4}
  ]},
  options:{responsive:true,maintainAspectRatio:false,scales:{x:{stacked:true,grid:{display:false},border:{display:false}},y:{stacked:true,beginAtZero:true,ticks:{precision:0},grid:{color:'rgba(145,155,178,.10)'},border:{display:false}}},plugins:{legend:{display:true,position:'bottom',labels:{usePointStyle:true,pointStyle:'circle',boxWidth:7,padding:18}}}}
});
