<div x-show="showModal" class="modal-overlay" style="display:none;" @click.self="closeModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Book Reservation</h3>
            <button @click="closeModal" class="modal-close">&times;</button>
        </div>

        <!-- Step Indicator (3 steps) -->
        <div class="step-indicator">
            <div>
                <div class="step-dot" :class="{ 'active': step >= 1 }">1</div>
                <div class="step-label">Date</div>
            </div>
            <div class="step-line" :class="{ 'active': step >= 2 }"></div>
            <div>
                <div class="step-dot" :class="{ 'active': step >= 2 }">2</div>
                <div class="step-label">Room &amp; Time</div>
            </div>
            <div class="step-line" :class="{ 'active': step >= 3 }"></div>
            <div>
                <div class="step-dot" :class="{ 'active': step >= 3 }">3</div>
                <div class="step-label">Details</div>
            </div>
        </div>

        <!-- Step 1: Date -->
        <div x-show="step === 1">
            <div class="input-group">
                <label>Select Date</label>
                <input type="text" id="datepicker" placeholder="Click to choose date" x-init="initDatePicker()">
            </div>
            <p style="font-size:0.85rem; color:var(--prof-muted);">Past dates and Sundays are not available.</p>
        </div>

        <!-- Step 2: Room Type → Room → Time Slot (all in one) -->
        <div x-show="step === 2">
            <p style="margin-bottom:1rem;">Selected Date: <strong x-text="selectedDate"></strong></p>
            <!-- Existing reservations for selected date -->
<div x-show="reservationsForSelectedDate.length > 0"
     class="selected-date-reservations">

    <div class="selected-date-header">
        Your reservations on this date
    </div>

    <template x-for="item in reservationsForSelectedDate">

        <div class="selected-date-item"

     style="cursor:pointer;"

     @click="
        showModal = false;

        let targetFilter =
            (item.status.toLowerCase() === 'approved' ||
             item.status.toLowerCase() === 'ongoing')
                ? 'approved_ongoing'
                : item.status.toLowerCase();

        window.dispatchEvent(
            new CustomEvent('jump-to-reservation', {
                detail: {
                    id: item.id,
                    filter: targetFilter
                }
            })
        );
     "
>

            <div class="selected-date-top">
                <span class="selected-room" x-text="item.room"></span>

                <span class="badge badge-approved"
                      x-text="item.status">
                </span>
            </div>

            <div class="selected-date-sub">
                <span x-text="item.slot"></span>
                ·
                <span x-text="item.activity"></span>
            </div>

        </div>

    </template>

</div>

            <!-- Choose room type -->
<div x-show="!roomTypeSelected">
    <p style="font-weight:600; margin-bottom:0.75rem;">Choose a room type:</p>

    <!-- Show spinner / text while rooms are loading -->
    <div x-show="loading" style="text-align:center; padding:1.5rem 0;">
        <p style="color:var(--prof-muted);">Loading available rooms…</p>
    </div>

    <!-- Buttons – disabled while loading -->
    <div x-show="!loading" style="display:flex; gap:1rem;">
        <button @click="filterByType('laboratory')" :disabled="loading" class="btn btn-primary" style="flex:1;">
            🧪 Laboratory
        </button>
        <button @click="filterByType('lecture')" :disabled="loading" class="btn btn-primary" style="flex:1;">
            📖 Lecture
        </button>
    </div>

    <p style="font-size:0.8rem; color:var(--prof-muted); margin-top:0.5rem;">
        Laboratory: 7‑12 pm, 12‑3 pm, 4‑9 pm &nbsp;|&nbsp; Lecture: 7‑10 am, 10‑1 pm, 1‑4 pm, 4‑7 pm
    </p>
</div>

            <!-- After type is chosen -->
            <div x-show="roomTypeSelected">
                <div x-show="loading">Loading rooms...</div>
                <div x-show="!loading">
                    <template x-if="filteredRooms.length === 0">
                        <p style="color:var(--prof-danger);">No rooms available on this date.</p>
                    </template>

                    <!-- Room cards with inline time‑slot expander -->
                    <template x-for="room in filteredRooms" :key="room.id">
                        <div class="room-block">
                            <div class="room-card" @click="toggleRoomTimeSlots(room.id)">
                                <div class="slot-card-title" x-text="room.name"></div>
                                <div class="slot-card-sub" x-text="room.type.charAt(0).toUpperCase() + room.type.slice(1)"></div>
                                <div class="slot-card-sub">
                                    Capacity: <span x-text="room.capacity"></span>
                                    &nbsp;·&nbsp;
                                    <span style="color:var(--prof-primary); font-weight:600;">
                                        <span x-text="room.time_slots.filter(s => s.available).length"></span>
                                        /
                                        <span x-text="room.time_slots.length"></span>
                                        slots free
                                    </span>
                                </div>
                            </div>

                            <!-- Inline time slots (shown/hidden) -->
                            <div x-show="expandedRoom === room.id" class="room-time-slots">
                                <div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-top:0.5rem;">
                                    <template x-for="slotData in room.time_slots" :key="slotData.slot">
                                        <div>
                                            <!-- Blocked slot -->
                                            <div x-show="slotData.blocked"
                                                 class="slot-pill blocked">
                                                <span x-text="slotData.slot"></span>
                                                <div class="pill-sub" x-text="slotData.block_info.label"></div>
                                            </div>
                                            <!-- Available slot -->
                                            <div x-show="!slotData.blocked && slotData.available"
                                                 @click="selectSlotAndProceed(room, slotData.slot)"
                                                 class="slot-pill available">
                                                <span x-text="slotData.slot"></span>
                                            </div>
                                            <!-- Unavailable slot -->
                                            <div x-show="!slotData.blocked && !slotData.available"
                                                 class="slot-pill unavailable">
                                                <span x-text="slotData.slot"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div style="margin-top:1rem; text-align:right;">
                <button @click="goBack" class="btn btn-outline">Back</button>
            </div>
        </div>

        <!-- Step 3: Details (formerly step 4) -->
        <div x-show="step === 3">
            <div class="card" style="background:#f8fafc; margin-bottom:1rem;">
                <p><strong>Date:</strong> <span x-text="selectedDate"></span></p>
                <p><strong>Room:</strong> <span x-text="selectedRoom?.name"></span></p>
                <p><strong>Time Slot:</strong> <span x-text="selectedTimeSlot"></span></p>
            </div>
            <div class="input-group">
                <label>Activity Name</label>
                <input type="text" x-model="activityName" required>
            </div>
            <div class="input-group">
                <label>Number of Pax</label>
                <input type="number" x-model="pax" min="1" required>
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:1.5rem;">
                <button @click="goBack" class="btn btn-outline">Back</button>
                <button @click="submitBooking" class="btn btn-primary" :disabled="loading">Submit Reservation</button>
            </div>
        </div>
    </div>
</div>