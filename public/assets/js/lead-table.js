jQuery(function ($) {

	/* columns ---------------------------------------------------------- */
	const cols = [
		["client_id","Client","select",LCM.clients],
		["ad_name","Ad Name","text"],
		["adset","Adset","select",LCM.adsets],
		["uid","UID","text"],
		["lead_date","Date","date"],
		["lead_time","Time","text"],
		["day","Day","select",["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"]],
		["phone_number","Phone","text"],
		["attempt","Att","select",[1,2,3,4,5,6]],
		["attempt_type","Att Type","select",["Connected:Not Relevant","Connected:Relevant","Not Connected"]],
		["attempt_status","Att Status","select",["Call Rescheduled","Just browsing","Not Interested","Ringing / No Response","Store Visit Scheduled","Wrong Number / Invalid Number"]],
		["store_visit_status","Store Visit","select",["Show","No Show"]],
		["remarks","Remarks","text"],
	];

	const $thead = $("#lcm-lead-table thead");
	const $tbody = $("#lcm-lead-table tbody");
	const $pager = $("#lcm-pager");
	const per    = LCM.per_page;
	let page     = 1;

	/* build header */
	$thead.html("<tr>"+cols.map(c=>`<th>${c[1]}</th>`).join("")+"</tr>");

	/* render dropdown options */
	function opts(arr,cur=""){
		return "<option value=''></option>"+arr.map(o=>{
			const v=Array.isArray(o)?o[0]:o;
			const t=Array.isArray(o)?o[1]:o;
			return `<option value="${v}"${v==cur?" selected":""}>${t}</option>`;
		}).join("");
	}
	/* render row */
	function rowHtml(r){
		let tr=`<tr data-id="${r.id||""}"${r.id?"":" class='table-warning'"}>`;
		cols.forEach(([f,_,t,arr])=>{
			const v=r[f]||"";
			if(t==="select"){
				tr+=`<td><select class='form-select form-select-sm' data-name='${f}'>${opts(arr,v)}</select></td>`;
			}else if(t==="date"){
				tr+=`<td><input type='date' class='form-control form-control-sm' data-name='${f}' value='${v}'></td>`;
			}else{
				tr+=`<td><input type='text' class='form-control form-control-sm' data-name='${f}' value='${v}'></td>`;
			}
		});
		tr+="</tr>"; return tr;
	}

	/* fetch page */
	function load(p=1){
		$.getJSON(LCM.ajax_url,{
			action:"lcm_get_leads_json", nonce:LCM.nonce,
			page:p, per_page:per
		},res=>{
			page=p;
			$tbody.html(res.rows.map(rowHtml).join(""));
			buildPager(res.total);
		});
	}
	function buildPager(total){
		const pages=Math.max(1,Math.ceil(total/per));
		let h="";
		for(let i=1;i<=pages;i++){
			h+=`<button class='btn btn-outline-secondary ${i===page?"active":""}' data-p='${i}'>${i}</button>`;
		}
		$pager.html(h);
	}

	/* pager click */
	$pager.on("click","button",e=>load(parseInt(e.currentTarget.dataset.p)));

	/* Add row */
	$("#lcm-add-row").on("click",()=>{
		const blank={}; cols.forEach(c=>blank[c[0]]="");
		$tbody.prepend(rowHtml(blank));
	});

	/* auto-fill Day when Date changes */
	$tbody.on("change","input[type=date]",function(){
		const date=this.value;
		if(date){
			const d=new Date(date+"T00:00:00");
			const day=["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"][d.getUTCDay()];
			$(this).closest("tr").find("[data-name=day]").val(day);
		}
	});

	/* save new row */
	$tbody.on("change blur","input,select",function(){
		const $tr=$(this).closest("tr");
		if($tr.data("id")) return;
		const d={action:"lcm_create_lead",nonce:LCM.nonce};
		$tr.find("input,select").each(function(){d[this.dataset.name]=$(this).val();});
		if(!d.uid||!d.adset)return;
		$.post(LCM.ajax_url,d,res=>{
			if(res.success) load(page); else alert(res.data.msg||"Save failed");
		},"json");
	});

	/* first load */
	load(1);
});
