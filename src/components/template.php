<?php 
require_once __DIR__ . '/header.php'; 

?>
 <div class="w-full max-w-2xl mb-8 mx-auto">
    
    <div class="flex justify-between items-center mb-8 bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
        
        <div class="flex items-center gap-4">
            <img src="/public/avatars/bee.png" alt="Avatar" class="w-16 h-16 rounded-full border-4 border-[#A3ADFF] object-cover shadow-sm">
            <span class="text-xl md:text-2xl font-bold text-[#281373]">Player One</span>
        </div>

        <div class="bg-[#281373] text-white px-6 py-2 md:py-3 rounded-xl shadow-md flex items-center gap-3">
            <span class="text-sm text-[#A3ADFF] font-medium uppercase tracking-wider hidden md:inline">Score</span>
            <span class="text-2xl md:text-3xl font-extrabold">0</span>
        </div>

    </div>

    <div class="w-full">
        <div class="flex justify-between items-end mb-2 px-2">
            <span class="text-[#281373] font-bold text-lg">Time Remaining</span>
            <span id="timerText" class="text-3xl font-extrabold text-[#281373]">15s</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-4 shadow-inner">
            <div id="timerBar" class="bg-[#8A96FF] h-4 rounded-full transition-all duration-1000 ease-linear" style="width: 100%"></div>
        </div>
    </div>

</div>


<div class="bg-[#A3ADFF] rounded-[2rem] p-8 mt-12 w-full max-w-4xl mx-auto" >
    
    <p class="text-black font-semibold text-lg mb-8 text-center ">
        question?
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <button class="bg-[#8A96FF] hover:bg-[#7885FF] w-full text-left rounded-full py-3 px-4 flex items-center shadow-md transition-colors">
    
            <span class="bg-white text-[#A3ADFF] rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg shrink-0">
                A
            </span>
    
            <span class="text-black font-semibold ml-4">
                A
            </span>

        </button>

        <button class="bg-[#8A96FF] hover:bg-[#7885FF] w-full text-left rounded-full py-3 px-4 flex items-center shadow-md transition-colors">
    
            <span class="bg-white text-[#A3ADFF] rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg shrink-0">
                B
            </span>
    
            <span class="text-black font-semibold ml-4">
                B
            </span>

        </button>

        <button class="bg-[#8A96FF] hover:bg-[#7885FF] w-full text-left rounded-full py-3 px-4 flex items-center shadow-md transition-colors">
    
            <span class="bg-white text-[#A3ADFF] rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg shrink-0">
                C
            </span>
    
            <span class="text-black font-semibold ml-4">
                C
            </span>

        </button>

        <button class="bg-[#8A96FF] hover:bg-[#7885FF] w-full text-left rounded-full py-3 px-4 flex items-center shadow-md transition-colors">
    
            <span class="bg-white text-[#A3ADFF] rounded-full w-10 h-10 flex items-center justify-center font-bold text-lg shrink-0">
                D
            </span>
    
            <span class="text-black font-semibold ml-4">
                D
            </span>

        </button>

    </div>
</div>



<script>
    let timeLeft = 15;
    const totalTime = 15;
    const timerText = document.getElementById('timerText');
    const timerBar = document.getElementById('timerBar');

    const countdown = setInterval(() => {
        timeLeft--;
        

        timerText.innerText = timeLeft + 's';
        
        const percentage = (timeLeft / totalTime) * 100;
        timerBar.style.width = percentage + '%';

        if (timeLeft <= 5) {
            timerText.classList.remove('text-[#281373]');
            timerText.classList.add('text-red-500');
            timerBar.classList.remove('bg-[#8A96FF]');
            timerBar.classList.add('bg-red-500');
        }

        if (timeLeft <= 0) {
            clearInterval(countdown);
            timerText.innerText = '0s';
           
        }
    }, 1000); 
</script>

<?php require_once __DIR__ . '/footer.php'; ?>