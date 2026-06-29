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

  const recepcionUsuario=document.getElementById('recepcionUsuario');
  if(recepcionUsuario){
    const detailRows=document.getElementById('detailRows'), empty=document.getElementById('recepcionEmpty'), save=document.getElementById('saveRecepcion'), add=document.getElementById('addRecepcionDetail');
    const firstRow=detailRows?.querySelector('.recepcion-row');
    const allOptions=firstRow?Array.from(firstRow.querySelector('.recepcion-detalle').options).map(o=>({value:o.value,text:o.textContent,user:o.dataset.user||''})):[];
    function fillSelect(select,user){
      select.innerHTML='<option value="">Seleccione material...</option>';
      allOptions.filter(o=>o.value&&o.user===user).forEach(o=>{const opt=document.createElement('option');opt.value=o.value;opt.textContent=o.text;opt.dataset.user=o.user;select.append(opt);});
    }
    function filterRecepcionDetails(){
      const user=recepcionUsuario.value, hasItems=allOptions.some(o=>o.value&&o.user===user);
      if(firstRow){detailRows.querySelectorAll('.recepcion-row').forEach((row,i)=>{if(i>0)row.remove();});fillSelect(firstRow.querySelector('.recepcion-detalle'),user);firstRow.querySelectorAll('input').forEach(i=>i.value='');}
      if(empty){empty.textContent=user?(hasItems?'Materiales pendientes para el usuario seleccionado.':'Este usuario no tiene materiales pendientes por devolver.'):'Seleccione un usuario para ver sus materiales pendientes.';empty.className=`alert ${hasItems?'alert-info':'alert-warning'} py-2`;}
      if(save)save.disabled=!user||!hasItems; if(add)add.disabled=!user||!hasItems;
    }
    recepcionUsuario.addEventListener('change',filterRecepcionDetails); filterRecepcionDetails();
  }
  const cs=document.getElementById('cableSelect');function loadCable(){if(!cs)return;const d=JSON.parse(cs.selectedOptions[0].dataset.json||'{}');document.getElementById('cableInfo').innerHTML=['calibre','marca','largo','tipo_enchufe','capacidad_aislacion'].map(k=>`<div><b>${k.replaceAll('_',' ')}</b><br>${d[k]||''}</div>`).join('')} if(cs){cs.addEventListener('change',loadCable);loadCable()}
  if(typeof Chart==='undefined')return;
  const gridColor='rgba(15,23,42,.10)', textColor='#334155';
  Chart.defaults.color=textColor; Chart.defaults.borderColor=gridColor;
  const emptyPlugin={id:'emptyState',afterDraw(chart){const has=chart.data.datasets.some(ds=>(ds.data||[]).some(v=>Number(v)>0)); if(has)return; const {ctx,chartArea:{left,right,top,bottom}}=chart;ctx.save();ctx.fillStyle='#aeb4c2';ctx.textAlign='center';ctx.fillText('Sin datos disponibles',(left+right)/2,(top+bottom)/2);ctx.restore();}};
  function values(id,labelKey,valueKey){const el=document.getElementById(id); if(!el)return null; const d=JSON.parse(el.dataset.values||'[]'); return {el,labels:d.map(x=>x[labelKey]||'Sin dato'),data:d.map(x=>Number(x[valueKey]||0))};}
  function doughnut(id,labelKey,valueKey,colors){const v=values(id,labelKey,valueKey); if(!v)return; new Chart(v.el,{type:'doughnut',data:{labels:v.labels,datasets:[{data:v.data,backgroundColor:colors,borderWidth:1}]},options:{maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}},plugins:[emptyPlugin]});}
  function bar(id,labelKey,valueKey,horizontal=false){const v=values(id,labelKey,valueKey); if(!v)return; new Chart(v.el,{type:'bar',data:{labels:v.labels,datasets:[{label:'Total',data:v.data,backgroundColor:'#d96716',borderRadius:5}]},options:{maintainAspectRatio:false,indexAxis:horizontal?'y':'x',scales:{x:{grid:{color:gridColor}},y:{grid:{color:gridColor},beginAtZero:true}},plugins:{legend:{display:false}}},plugins:[emptyPlugin]});}
  doughnut('chartEstados','estado','total',['#d96716','#d99a22','#2563eb','#b91c1c']);
  doughnut('chartInformes','estado','total',['#94a3b8','#d96716','#b91c1c']);
  bar('chartFallas','opcion','total'); bar('chartCausas','opcion','total',true); bar('chartMateriales','nombre_material','total');
});
function addDetail(){const c=document.querySelector('#detailRows .row').cloneNode(true);c.querySelectorAll('input').forEach(i=>i.value='');document.getElementById('detailRows').appendChild(c)}

// Automatización transversal de formularios: foco inicial, ayudas visuales,
// borradores por sesión, marcado de requeridos y protección contra doble envío.
document.addEventListener('DOMContentLoaded',()=>{
  const today=new Date().toISOString().slice(0,10);
  document.querySelectorAll('form').forEach((form,idx)=>{
    if(form.closest('.login-card')) return;
    form.classList.add('form-enhanced');
    const key=`seim:draft:${location.pathname}:${idx}`;
    const fields=Array.from(form.querySelectorAll('input,select,textarea')).filter(el=>el.name&&!['hidden','password','file'].includes(el.type));
    fields.forEach(el=>{
      const label=el.id?form.querySelector(`label[for="${el.id}"]`):el.closest('div')?.querySelector('label');
      if(el.required&&label&&!label.querySelector('.required-mark')) label.insertAdjacentHTML('beforeend','<span class="required-mark">*</span>');
      if(el.type==='date'&&el.required&&!el.value){el.value=today;el.classList.add('is-autofilled');}
      if(el.tagName==='TEXTAREA'){
        const counter=document.createElement('div'); counter.className='form-counter';
        const update=()=>{counter.textContent=`${el.value.length} caracteres`;};
        el.insertAdjacentElement('afterend',counter); el.addEventListener('input',update); update();
      }
    });
    const saved=sessionStorage.getItem(key);
    if(saved){
      const values=JSON.parse(saved);
      fields.forEach(el=>{if(values[el.name]!==undefined&&!el.value){el.value=values[el.name];el.classList.add('is-autofilled');}});
    }
    const persist=()=>{
      const values={}; fields.forEach(el=>{if(el.value) values[el.name]=el.value;});
      if(Object.keys(values).length) sessionStorage.setItem(key,JSON.stringify(values));
    };
    fields.forEach(el=>el.addEventListener('input',persist));
    const first=fields.find(el=>!el.disabled&&!el.readOnly&&el.offsetParent!==null);
    if(first&&!document.querySelector(':focus')) first.focus({preventScroll:true});
    form.addEventListener('submit',()=>{
      sessionStorage.removeItem(key);
      const submit=form.querySelector('button[type="submit"],button:not([type])');
      if(submit){submit.disabled=true;submit.classList.add('is-saving');submit.dataset.originalText=submit.textContent;submit.textContent='Guardando...';}
    });
    form.addEventListener('invalid',ev=>{ev.target.classList.add('is-invalid-soft');},true);
    form.addEventListener('input',ev=>{ev.target.classList?.remove('is-invalid-soft');},true);
  });
});
