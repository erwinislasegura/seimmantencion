document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('.datatable').forEach(t=>new DataTable(t,{language:{url:'https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json'},pageLength:10,compact:true,responsive:true}));
  const cs=document.getElementById('cableSelect');function loadCable(){if(!cs)return;const d=JSON.parse(cs.selectedOptions[0].dataset.json||'{}');document.getElementById('cableInfo').innerHTML=['calibre','marca','largo','tipo_enchufe','capacidad_aislacion'].map(k=>`<div><b>${k.replaceAll('_',' ')}</b><br>${d[k]||''}</div>`).join('')} if(cs){cs.addEventListener('change',loadCable);loadCable()}
  const gridColor='rgba(255,255,255,.08)', textColor='#d8deea';
  Chart.defaults.color=textColor; Chart.defaults.borderColor=gridColor;
  const emptyPlugin={id:'emptyState',afterDraw(chart){const has=chart.data.datasets.some(ds=>(ds.data||[]).some(v=>Number(v)>0)); if(has)return; const {ctx,chartArea:{left,right,top,bottom}}=chart;ctx.save();ctx.fillStyle='#aeb4c2';ctx.textAlign='center';ctx.fillText('Sin datos disponibles',(left+right)/2,(top+bottom)/2);ctx.restore();}};
  function values(id,labelKey,valueKey){const el=document.getElementById(id); if(!el)return null; const d=JSON.parse(el.dataset.values||'[]'); return {el,labels:d.map(x=>x[labelKey]||'Sin dato'),data:d.map(x=>Number(x[valueKey]||0))};}
  function doughnut(id,labelKey,valueKey,colors){const v=values(id,labelKey,valueKey); if(!v)return; new Chart(v.el,{type:'doughnut',data:{labels:v.labels,datasets:[{data:v.data,backgroundColor:colors,borderWidth:1}]},options:{plugins:{legend:{position:'bottom'}}},plugins:[emptyPlugin]});}
  function bar(id,labelKey,valueKey,horizontal=false){const v=values(id,labelKey,valueKey); if(!v)return; new Chart(v.el,{type:'bar',data:{labels:v.labels,datasets:[{label:'Total',data:v.data,backgroundColor:'#8cc63f',borderRadius:5}]},options:{indexAxis:horizontal?'y':'x',scales:{x:{grid:{color:gridColor}},y:{grid:{color:gridColor},beginAtZero:true}},plugins:{legend:{display:false}}},plugins:[emptyPlugin]});}
  doughnut('chartEstados','estado','total',['#8cc63f','#d6c12f','#5867f2','#ff5b5b']);
  doughnut('chartInformes','estado','total',['#aeb4c2','#8cc63f','#ff5b5b']);
  bar('chartFallas','opcion','total'); bar('chartCausas','opcion','total',true); bar('chartMateriales','nombre_material','total');
});
function addDetail(){const c=document.querySelector('#detailRows .row').cloneNode(true);c.querySelectorAll('input').forEach(i=>i.value='');document.getElementById('detailRows').appendChild(c)}
