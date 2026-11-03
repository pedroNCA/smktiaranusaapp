document.addEventListener("DOMContentLoaded", function () {

    const search = document.getElementById("searchSiswa");
    const filter = document.getElementById("filterStatus");
    const rows = document.querySelectorAll("tbody tr");

    const btnEdit = document.getElementById("btnEdit");
    const btnSimpan = document.getElementById("btnSimpan");
    const btnBatal = document.getElementById("btnBatal");
    const btnHadirSemua = document.getElementById("btnHadirSemua");

    const originalData = [];

    let editMode = false;

    rows.forEach(function(row){

        originalData.push({

            status: row.querySelector(".status-select").value,

            jam: row.querySelector(".jam-input").value

        });

    });

    function setEditable(enable){

        editMode = enable;

        document.querySelectorAll(".status-select").forEach(el=>{
            el.disabled = !enable;
        });

        document.querySelectorAll(".jam-hour").forEach(el=>{
            el.disabled = !enable;
        });

        document.querySelectorAll(".jam-minute").forEach(el=>{
            el.disabled = !enable;
        });

        if(btnHadirSemua){
            btnHadirSemua.disabled = !enable;
        }

    }

    setEditable(false);

    if(btnEdit){

        btnEdit.addEventListener("click",function(){

            setEditable(true);

            btnEdit.style.display = "none";
            btnSimpan.style.display = "inline-flex";
            btnBatal.style.display = "inline-flex";

        });

    }

    if(btnBatal){

        btnBatal.addEventListener("click",function(){

            rows.forEach(function(row,index){

                const status = row.querySelector(".status-select");
                const jam = row.querySelector(".jam-input");
                const hour = row.querySelector(".jam-hour");
                const minute = row.querySelector(".jam-minute");

                status.value = originalData[index].status;
                jam.value = originalData[index].jam;

                if(jam.value){

                    const p = jam.value.split(":");

                    hour.value = p[0];
                    minute.value = p[1];

                }else{

                    hour.value = "";
                    minute.value = "";

                }

                setStatusColor(status);

            });

            setEditable(false);

            btnEdit.style.display = "inline-flex";
            btnSimpan.style.display = "none";
            btnBatal.style.display = "none";

        });

    }

if(btnSimpan){

    btnSimpan.addEventListener("click",function(){

        const data=[];

        document.querySelectorAll("tbody tr").forEach(function(row){

            const status=row.querySelector(".status-select");

            const jam=row.querySelector(".jam-input");

            data.push({

                absensi_id:status.dataset.id,

                status:status.value,

                jam:jam.value

            });

        });

        btnSimpan.disabled=true;

        btnSimpan.innerHTML='<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

        fetch("ajax/simpan_perubahan.php",{

            method:"POST",

            headers:{
                "Content-Type":"application/x-www-form-urlencoded"
            },

            body:new URLSearchParams({

                data:JSON.stringify(data)

            })

        })

        .then(res=>res.json())

        .then(res=>{

if(res.success){

    showToast(res.message);

    originalData.length=0;

    rows.forEach(function(row){

        originalData.push({

            status:row.querySelector(".status-select").value,

            jam:row.querySelector(".jam-input").value

        });

    });

    setEditable(false);

    btnEdit.style.display="inline-flex";
    btnSimpan.style.display="none";
    btnBatal.style.display="none";

}

        })

        .catch(()=>{

            showToast("Gagal menyimpan data.","#DC2626");

        })

        .finally(()=>{

            btnSimpan.disabled=false;

            btnSimpan.innerHTML='<i class="fas fa-save"></i> Simpan Perubahan';

        });

    });

}

function setStatusColor(select){

select.classList.remove(
"hadir",
"sakit",
"izin",
"alfa",
"terlambat",
"belum"
);

switch(select.value){

case "Hadir":
select.classList.add("hadir");
break;

case "Sakit":
select.classList.add("sakit");
break;

case "Izin":
select.classList.add("izin");
break;

case "Alfa":
select.classList.add("alfa");
break;

case "Terlambat":
select.classList.add("terlambat");
break;

default:
select.classList.add("belum");

}

}

document.querySelectorAll(".status-select").forEach(function(select){

setStatusColor(select);

select.addEventListener("change",function(){

    setStatusColor(this);

    const row=this.closest("tr");

    const hidden=row.querySelector(".jam-input");

    const jam=row.querySelector(".jam-hour");

    const menit=row.querySelector(".jam-minute");

    if(this.value==="Terlambat"){

        if(hidden.value===""){

            const now=new Date();

            const hh=String(now.getHours()).padStart(2,"0");

            const mm=String(now.getMinutes()).padStart(2,"0");

            jam.value=hh;

            menit.value=mm;

            hidden.value=hh+":"+mm+":00";

        }

    }

    if(
        this.value==="Alfa"||
        this.value==="Sakit"||
        this.value==="Izin"||
        this.value==="Belum ada keterangan"
    ){

        jam.value="";

        menit.value="";

        hidden.value="";

    }

});

});

function filterTable(){

const keyword=search.value.toLowerCase();

const status=filter.value.toLowerCase().trim();

rows.forEach(function(row){

const nama=(row.dataset.nama||"").toLowerCase();

const stat=(row.querySelector(".status-select").value||"").toLowerCase().trim();

const cocokNama=nama.includes(keyword);

const cocokStatus=
status===""||
stat===status;

row.style.display=(cocokNama&&cocokStatus)?"":"none";

});

}

search.addEventListener("keyup",filterTable);

filter.addEventListener("change",filterTable);

const btnVerifikasi = document.getElementById("btnVerifikasi");
const btnFinalisasi = document.getElementById("btnFinalisasi");

const btn = btnVerifikasi || btnFinalisasi;

if (btn) {

    btn.addEventListener("click", function () {

        const id = this.dataset.id;

        this.disabled = true;

        const isFinalisasi = !!btnFinalisasi;

        const currentPage = window.location.pathname;
        const isWali = currentPage.endsWith("presensi_wali.php");

        if (isFinalisasi) {

            btn.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i> Memfinalisasi...';

        } else {

            btn.innerHTML = isWali
                ? '<i class="fas fa-spinner fa-spin"></i> Memverifikasi Wali...'
                : '<i class="fas fa-spinner fa-spin"></i> Memverifikasi...';

        }

        fetch(
            isFinalisasi
                ? "ajax/finalisasi.php"
                : "ajax/verifikasi.php",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: new URLSearchParams({
                    id: id
                })
            }
        )

        .then(res => res.json())

        .then(res => {

            if (res.success) {

                showToast(
                    isFinalisasi
                        ? "Finalisasi berhasil."
                        : (
                            isWali
                                ? "Verifikasi wali kelas berhasil."
                                : "Verifikasi berhasil."
                        )
                );

                setTimeout(() => {

                    window.location = "verifikasi.php";

                }, 1200);

            } else {

                showToast(res.message, "#DC2626");

                btn.disabled = false;

                if (isFinalisasi) {

                    btn.innerHTML =
                        '<i class="fas fa-flag-checkered"></i> Finalisasi';

                } else {

                    btn.innerHTML = isWali
                        ? '<i class="fas fa-check-circle"></i> Verifikasi Wali Kelas'
                        : '<i class="fas fa-check-circle"></i> Verifikasi';

                }

            }

        })

        .catch(() => {

            showToast("Terjadi kesalahan server", "#DC2626");

            btn.disabled = false;

            if (isFinalisasi) {

                btn.innerHTML =
                    '<i class="fas fa-flag-checkered"></i> Finalisasi';

            } else {

                btn.innerHTML = isWali
                    ? '<i class="fas fa-check-circle"></i> Verifikasi Wali Kelas'
                    : '<i class="fas fa-check-circle"></i> Verifikasi';

            }

        });

    });

}

function showToast(message,color="#16A34A"){

const toast=document.createElement("div");

toast.innerHTML=message;

toast.style.position="fixed";

toast.style.right="30px";

toast.style.top="30px";

toast.style.background=color;

toast.style.color="#fff";

toast.style.padding="12px 20px";

toast.style.borderRadius="12px";

toast.style.boxShadow="0 5px 15px rgba(0,0,0,.2)";

toast.style.zIndex="99999";

toast.style.opacity="0";

toast.style.transition=".3s";

document.body.appendChild(toast);

setTimeout(()=>{

toast.style.opacity="1";

},10);

setTimeout(()=>{

toast.style.opacity="0";

setTimeout(()=>toast.remove(),300);

},2000);

}
});