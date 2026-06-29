document.addEventListener('DOMContentLoaded',()=>{
  function enhanceTable(table){
    const tbody=table.tBodies[0]; if(!tbody)return;
    const allRows=Array.from(tbody.rows).filter(r=>r.cells.length===table.tHead?.rows[0]?.cells.length);
    const pageSize=Number(table.dataset.pageSize||10);
    const wrap=document.createElement('div'); wrap.className='simple-table-wrap';
    const tools=document.createElement('div'); tools.className='simple-table-tools';
    const search=document.createElement('input'); search.className='form-control form-control-sm simple-table-search'; search.placeholder='Buscar en tabla...';
    const info=document.createElement('span'); info.className='muted simple-table-info';
    const pager=document.createElement('div'); pager.className='simple-table-pager';
    const prev=document.createElement('button'); prev.type='button'; prev.className='btn btn-sm btn-outline-light'; prev.textContent='Anterior';
    const next=document.createElement('button'); next.type='button'; next.className='btn btn-sm btn-outline-light'; next.textContent='Siguiente';
    pager.append(prev,next); tools.append(search,info,pager);
    table.parentNode.insertBefore(wrap,table); wrap.append(tools,table);
    let page=1;
    function render(){
      const q=search.value.trim().toLowerCase();
      const filtered=allRows.filter(r=>!q||r.textContent.toLowerCase().includes(q));
      const pages=Math.max(1,Math.ceil(filtered.length/pageSize)); page=Math.min(page,pages);
      const start=(page-1)*pageSize, shown=new Set(filtered.slice(start,start+pageSize));
      allRows.forEach(r=>{r.style.display=shown.has(r)?'':'none'});
      let empty=tbody.querySelector('tr.simple-table-empty');
      if(!filtered.length){
        if(!empty){empty=document.createElement('tr'); empty.className='simple-table-empty'; empty.innerHTML=`<td class="text-center muted py-3" colspan="${table.tHead?.rows[0]?.cells.length||1}">Sin registros para mostrar</td>`; tbody.append(empty);}
        empty.style.display='';
      } else if(empty) empty.style.display='none';
      info.textContent=filtered.length?`${start+1}-${Math.min(start+pageSize,filtered.length)} de ${filtered.length}`:'0 registros';
      prev.disabled=page<=1; next.disabled=page>=pages;
    }
    search.addEventListener('input',()=>{page=1;render()}); prev.addEventListener('click',()=>{page--;render()}); next.addEventListener('click',()=>{page++;render()}); render();
  }
  document.querySelectorAll('.datatable').forEach(enhanceTable);
  const cs=document.getElementById('cableSelect');function loadCable(){if(!cs)return;const d=JSON.parse(cs.selectedOptions[0].dataset.json||'{}');document.getElementById('cableInfo').innerHTML=['calibre','marca','largo','tipo_enchufe','capacidad_aislacion'].map(k=>`<div><b>${k.replaceAll('_',' ')}</b><br>${d[k]||''}</div>`).join('')} if(cs){cs.addEventListener('change',loadCable);loadCable()}
  if(typeof Chart==='undefined')return;
  const gridColor='rgba(255,255,255,.08)', textColor='#d8deea';
  Chart.defaults.color=textColor; Chart.defaults.borderColor=gridColor;
  const emptyPlugin={id:'emptyState',afterDraw(chart){const has=chart.data.datasets.some(ds=>(ds.data||[]).some(v=>Number(v)>0)); if(has)return; const {ctx,chartArea:{left,right,top,bottom}}=chart;ctx.save();ctx.fillStyle='#aeb4c2';ctx.textAlign='center';ctx.fillText('Sin datos disponibles',(left+right)/2,(top+bottom)/2);ctx.restore();}};
  function values(id,labelKey,valueKey){const el=document.getElementById(id); if(!el)return null; const d=JSON.parse(el.dataset.values||'[]'); return {el,labels:d.map(x=>x[labelKey]||'Sin dato'),data:d.map(x=>Number(x[valueKey]||0))};}
  function doughnut(id,labelKey,valueKey,colors){const v=values(id,labelKey,valueKey); if(!v)return; new Chart(v.el,{type:'doughnut',data:{labels:v.labels,datasets:[{data:v.data,backgroundColor:colors,borderWidth:1}]},options:{maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}},plugins:[emptyPlugin]});}
  function bar(id,labelKey,valueKey,horizontal=false){const v=values(id,labelKey,valueKey); if(!v)return; new Chart(v.el,{type:'bar',data:{labels:v.labels,datasets:[{label:'Total',data:v.data,backgroundColor:'#8cc63f',borderRadius:5}]},options:{maintainAspectRatio:false,indexAxis:horizontal?'y':'x',scales:{x:{grid:{color:gridColor}},y:{grid:{color:gridColor},beginAtZero:true}},plugins:{legend:{display:false}}},plugins:[emptyPlugin]});}
  doughnut('chartEstados','estado','total',['#8cc63f','#d6c12f','#5867f2','#ff5b5b']);
  doughnut('chartInformes','estado','total',['#aeb4c2','#8cc63f','#ff5b5b']);
  bar('chartFallas','opcion','total'); bar('chartCausas','opcion','total',true); bar('chartMateriales','nombre_material','total');
});
function addDetail(){const c=document.querySelector('#detailRows .row').cloneNode(true);c.querySelectorAll('input').forEach(i=>i.value='');document.getElementById('detailRows').appendChild(c)}
