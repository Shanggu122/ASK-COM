// your Agora credentials
const APP_ID = "ab155f23c3fc4ae980b11973d818c460";
const TOKEN =
    "007eJxTYBB7sH2B1evi8MNMvhtm+twQlZJ1/Vi8aoGryK1H4grGWt8VGMxNjYyTTVLNjI3NzUzMjQwtUo0tTc3TjA0Mzc0sDIxSf7QczGgIZGSoXabPwsgAgSA+O0NJanGJoZExAwMAMtoeWg=="; // null if token not needed
const CHANNEL = decodeURIComponent(location.pathname.split("/").pop());

let client, localAudioTrack, localVideoTrack;
let micMuted = false;
let camOff = false;

// grab the UI
const localContainer = document.getElementById("local-player");
const remoteContainer = document.getElementById("remote-player");
const micBtn = document.getElementById("toggle-mic");
const camBtn = document.getElementById("toggle-cam");
const leaveBtn = document.getElementById("leave-btn");
const localStatus = document.getElementById("local-status");
const remoteStatus = document.getElementById("remote-status");

// 1) initialize and join on load
window.addEventListener("DOMContentLoaded", async () => {
    try {
        client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });

        // listen for other users
        client.on("user-published", handleUserPublished);
        client.on("user-unpublished", handleUserUnpublished);

        // join channel
        await client.join(APP_ID, CHANNEL, TOKEN, null);

        // create & publish your tracks
        [localAudioTrack, localVideoTrack] =
            await AgoraRTC.createMicrophoneAndCameraTracks();
        localVideoTrack.play(localContainer);
        await client.publish([localAudioTrack, localVideoTrack]);

        // Connected to channel
    } catch (err) {
        alert("Connection failed. Check APP_ID/TOKEN.");
    }
});

// Add control button event listeners
if (micBtn) {
    micBtn.addEventListener("click", () => {
        if (!localAudioTrack) return;
        micMuted = !micMuted;
        localAudioTrack.setEnabled(!micMuted);
        micBtn.innerHTML = micMuted
            ? "<i class='bx bxs-microphone-off'></i>Unmute"
            : "<i class='bx bxs-microphone'></i>Mute";
        if (localStatus) {
            localStatus.classList.toggle("hidden", !micMuted);
        }
    });
}

if (camBtn) {
    camBtn.addEventListener("click", () => {
        if (!localVideoTrack) return;
        camOff = !camOff;
        localVideoTrack.setEnabled(!camOff);
        camBtn.innerHTML = camOff
            ? "<i class='bx bxs-video-off'></i>Show"
            : "<i class='bx bxs-video'></i>Video";
        // Optional: Update status icon here if desired
    });
}

// 2) subscribe & play remote
async function handleUserPublished(user, mediaType) {
    await client.subscribe(user, mediaType);
    if (mediaType === "video") {
        user.videoTrack.play(remoteContainer);
        if (remoteStatus) {
            remoteStatus.classList.add("hidden");
        }
    }
    if (mediaType === "audio") {
        user.audioTrack.play();
    }
}

// 3) clear UI when they leave
function handleUserUnpublished(user) {
    remoteContainer.innerHTML = "";
    if (remoteStatus) {
        remoteContainer.appendChild(remoteStatus);
        remoteStatus.classList.remove("hidden");
    }
}

// 4) leave button
if (leaveBtn) {
    leaveBtn.onclick = async () => {
        if (localAudioTrack) localAudioTrack.close();
        if (localVideoTrack) localVideoTrack.close();
        await client.leave();
        location.href = "/messages"; // back to your chat page
    };
}
