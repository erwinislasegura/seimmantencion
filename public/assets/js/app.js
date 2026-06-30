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
  const cs=document.getElementById('cableSelect');function loadCable(){if(!cs)return;const selected=cs.selectedOptions[0];const saved=cs.dataset.snapshot?JSON.parse(cs.dataset.snapshot||'{}'):{};const live=JSON.parse(selected?.dataset.json||'{}');const d=Object.keys(saved).length?saved:live;const info=document.getElementById('cableInfo');if(info)info.innerHTML=['numero_cable','calibre','marca','largo','tipo_enchufe','aislacion','capacidad_aislacion'].map(k=>`<div><b>${k.replaceAll('_',' ')}</b><br>${d[k]||''}</div>`).join('')} if(cs){cs.addEventListener('change',()=>{cs.dataset.snapshot='';loadCable();});loadCable()}
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

// Materiales usados en informes: permite múltiples usuarios y filtra materiales por usuario.
document.addEventListener('DOMContentLoaded',()=>{
  const wrap=document.getElementById('informeMaterialRows'), add=document.getElementById('addInformeMaterial');
  if(!wrap||!add) return;
  const first=wrap.querySelector('.informe-material-row');
  if(!first) return;
  const baseOptions=Array.from(first.querySelector('.informe-material-select').options).map(o=>({value:o.value,text:o.textContent,user:o.dataset.user||'',disponible:o.dataset.disponible||''}));
  function fill(row,preserve=false){
    const user=row.querySelector('.informe-material-user').value;
    const select=row.querySelector('.informe-material-select');
    const qty=row.querySelector('.informe-material-cantidad');
    const selected=preserve?select.value:'';
    const quantity=preserve?qty.value:'';
    select.innerHTML='<option value="">Seleccione material...</option>';
    baseOptions.filter(o=>o.value&&o.user===user).forEach(o=>{const opt=document.createElement('option');opt.value=o.value;opt.textContent=o.text;opt.dataset.user=o.user;opt.dataset.disponible=o.disponible;if(o.value===selected)opt.selected=true;select.append(opt);});
    select.disabled=!user; qty.disabled=!user; qty.value=quantity; qty.removeAttribute('max');
    const max=select.selectedOptions[0]?.dataset.disponible||''; if(max) qty.max=max;
  }
  function bind(row){
    const user=row.querySelector('.informe-material-user'), select=row.querySelector('.informe-material-select'), qty=row.querySelector('.informe-material-cantidad'), remove=row.querySelector('.remove-informe-material');
    user.addEventListener('change',()=>fill(row));
    select.addEventListener('change',()=>{const max=select.selectedOptions[0]?.dataset.disponible||''; if(max) qty.max=max;});
    remove.addEventListener('click',()=>{if(wrap.querySelectorAll('.informe-material-row').length>1) row.remove(); else row.querySelectorAll('select,input').forEach(el=>el.value=''); fill(row);});
    fill(row,true);
  }
  wrap.querySelectorAll('.informe-material-row').forEach(bind);
  add.addEventListener('click',()=>{const row=first.cloneNode(true);row.querySelectorAll('select,input').forEach(el=>el.value='');wrap.append(row);bind(row);});
});


// Tarjetas de revisión de informes: solo permite marcar "Con falla" cuando la prueba está en Sí.
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('.revision-card-realizada').forEach(realizada=>{
    const row=realizada.closest('.switch-line,.input-group');
    const falla=row?.querySelector('.revision-card-falla');
    if(!falla) return;
    const sync=()=>{falla.disabled=!realizada.checked; if(!realizada.checked) falla.checked=false;};
    realizada.addEventListener('change',sync);
    sync();
  });
});

// Confirmación detallada antes de guardar informes: muestra qué datos se enviarán
// y bloquea el guardado si falta un campo obligatorio del formulario.
document.addEventListener('DOMContentLoaded',()=>{
  const form=document.getElementById('informeCableForm');
  const modalEl=document.getElementById('confirmInformeModal');
  const summary=document.getElementById('confirmInformeSummary');
  const errors=document.getElementById('confirmInformeErrors');
  const confirmBtn=document.getElementById('confirmInformeSubmit');
  if(!form||!modalEl||!summary||!confirmBtn) return;
  const modal=window.bootstrap?new bootstrap.Modal(modalEl):null;
  const labels={
    supervisor_id:'Supervisor',fecha_recepcion_cable:'Fecha recepción',fecha_entrega_cable:'Fecha entrega',cable_id:'Cable',estado_informe:'Estado informe',origen_cable:'Origen cable',
    rep_ing_mufas_termo:'Ingreso · Mufas termocontraíbles',rep_ing_mufa_union:'Ingreso · Mufa de unión',rep_ing_chaquetas:'Ingreso · Chaquetas',rep_sal_mufas_termo:'Salida · Mufas termocontraíbles',rep_sal_mufa_union:'Salida · Mufa de unión',rep_sal_chaquetas:'Salida · Chaquetas',
    estado_operativo:'Estado operativo',destino_cable:'Destino',tipo_enchufe_entrega:'Tipo enchufe entrega',largo_entrega:'Largo entrega',marca_entrega:'Marca entrega',capacidad_aislacion_entrega:'Capacidad aislación entrega',
    fallas_chaquetas:'Falla chaquetas/mufas',fallas_enchufe:'Falla enchufe',lugares_falla:'Lugar de falla',causas_probables:'Causa probable',material_detalle_id:'Materiales usados',material_cantidad:'Cantidades usadas',observacion_final:'Observación final'
  };
  const sections=[['Cabecera',['supervisor_id','fecha_recepcion_cable','fecha_entrega_cable','cable_id','estado_informe','origen_cable']],['Reparaciones',['rep_ing_mufas_termo','rep_ing_mufa_union','rep_ing_chaquetas','rep_sal_mufas_termo','rep_sal_mufa_union','rep_sal_chaquetas']],['Entrega',['estado_operativo','destino_cable','tipo_enchufe_entrega','largo_entrega','marca_entrega','capacidad_aislacion_entrega']],['Diagnóstico',['fallas_chaquetas','fallas_enchufe','lugares_falla','causas_probables']],['Materiales y cierre',['material_detalle_id','material_cantidad','observacion_final']]];
  const cleanName=name=>name.replace(/\[.*$/,'');
  const fieldText=el=>{
    if(el.tagName==='SELECT') return Array.from(el.selectedOptions).map(o=>o.textContent.trim()).filter(Boolean).join(', ');
    if(el.type==='checkbox') return el.checked?((el.closest('label')?.textContent||el.value).trim()):'';
    return (el.value||'').trim();
  };
  const valuesFor=base=>Array.from(form.elements).filter(el=>el.name&&cleanName(el.name)===base&&!['hidden','submit','button'].includes(el.type)).map(fieldText).filter(Boolean);
  function renderSummary(){
    const missing=[];
    form.querySelectorAll('[required]').forEach(el=>{if(!el.value) missing.push((el.closest('div')?.querySelector('label')?.textContent||el.name).trim());});
    errors.classList.toggle('d-none',missing.length===0);
    errors.innerHTML=missing.length?`Faltan campos obligatorios: <strong>${missing.join(', ')}</strong>`:'';
    summary.innerHTML='';
    sections.forEach(([title,fields])=>{
      const h=document.createElement('div'); h.className='confirm-section'; h.textContent=title; summary.append(h);
      fields.forEach(name=>{
        const vals=valuesFor(name);
        if(!vals.length) return;
        const item=document.createElement('div'); item.className='confirm-item';
        item.innerHTML=`<b>${labels[name]||name}</b><span>${vals.join(', ')}</span>`; summary.append(item);
      });
    });
    return missing.length===0;
  }
  form.addEventListener('submit',ev=>{
    if(form.dataset.confirmed==='1') return;
    ev.preventDefault(); ev.stopImmediatePropagation();
    if(!form.checkValidity()){form.reportValidity(); return;}
    const ok=renderSummary(); confirmBtn.disabled=!ok;
    if(modal) modal.show(); else if(ok&&window.confirm('¿Confirma guardar el informe?')){form.dataset.confirmed='1';form.requestSubmit();}
  },true);
  confirmBtn.addEventListener('click',()=>{form.dataset.confirmed='1'; if(modal) modal.hide(); form.requestSubmit();});
});
