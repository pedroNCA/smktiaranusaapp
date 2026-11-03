function updateClock(){

    const now = new Date();

    const jam = String(now.getHours()).padStart(2,'0');
    const menit = String(now.getMinutes()).padStart(2,'0');
    const detik = String(now.getSeconds()).padStart(2,'0');

    const clock = document.getElementById("liveClock");

    if(clock){

        clock.textContent=`${jam}:${menit}:${detik}`;

    }

}

updateClock();

setInterval(updateClock,1000);

/* ========================= */

function toggleDropdown(){

    document
        .getElementById("userDropdown")
        .classList
        .toggle("show");

}

window.onclick=function(e){

    if(!e.target.closest(".user-dropdown")){

        const menu=document.getElementById("userDropdown");

        if(menu){

            menu.classList.remove("show");

        }

    }

}