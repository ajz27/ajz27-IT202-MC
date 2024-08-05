<?php
// ensures this script receives the necessary parameters
$rating = isset($song['rating']) ? intval($song['rating']) : 0; // default to 0 if not set
$track_id = isset($song['id']) ? $song['id'] : 0;

// check if the rating should be editable or read-only
$readonly = isset($readonly) && $readonly ? 'readonly' : '';
?>

<div class="star-rating <?php echo htmlspecialchars($readonly); ?>" data-track-id="<?php echo htmlspecialchars($track_id); ?>">
    <input type="hidden" id="rating-<?php echo htmlspecialchars($track_id); ?>" name="rating" value="<?php echo htmlspecialchars($rating); ?>">
    <div class="stars <?php echo htmlspecialchars($readonly); ?>">
        <?php for ($i = 1; $i <= 5; $i++) : ?>
            <span class="star <?php echo $i <= $rating ? 'selected' : ''; ?>" data-value="<?php echo $i; ?>">★</span>
        <?php endfor; ?>
    </div>
</div>

<style>
.star-rating {
    display: inline-block;
    position: relative;
}

.stars {
    display: flex;
}

.star {
    font-size: 1.5rem;
    color: #ddd;
    cursor: pointer; /* Added to change the cursor to pointer */
}

.star.selected {
    color: gold;
}

.star-rating.readonly .star {
    cursor: default; /* Retains default cursor for readonly stars */
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // only add click event listeners if not in readonly mode
    document.querySelectorAll('.star-rating:not(.readonly)').forEach(function(starRatingElement) {
        const stars = starRatingElement.querySelectorAll('.star');
        const ratingInput = starRatingElement.querySelector('input[name="rating"]');

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.value);
                ratingInput.value = rating;
                stars.forEach(s => s.classList.toggle('selected', parseInt(s.dataset.value) <= rating));
            });
        });
    });
});
</script>
