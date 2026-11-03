function updateClock() {

    const now = new Date();

    const jam = String(now.getHours()).padStart(2, '0');
    const menit = String(now.getMinutes()).padStart(2, '0');
    const detik = String(now.getSeconds()).padStart(2, '0');

    const clock = document.getElementById("liveClock");

    if(clock){

        clock.textContent=`${jam}:${menit}:${detik}`;

    }

}

updateClock();

setInterval(updateClock,1000);

// =========================

const search=document.getElementById("searchClass");

const filter=document.getElementById("statusFilter");

const cards=document.querySelectorAll(".kelas-card");

function filterCard(){

    const keyword=search.value.toLowerCase();

    const status=filter.value;

    cards.forEach(card=>{

        const nama=card.dataset.kelas;

        const sts=card.dataset.status;

        const cocokNama=nama.includes(keyword);

        const cocokStatus=status==="all" || sts===status;

        card.style.display=(cocokNama && cocokStatus)
            ? ""
            : "none";

    });

}

search.addEventListener("keyup",filterCard);

filter.addEventListener("change",filterCard);