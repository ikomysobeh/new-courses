# How Attention Score is Calculated

**File:** `app/Services/OnlineCourse/User/LearningSessionService.php`  
**Method:** `calculateAttentionScore()`  
**When:** Called automatically when a user **ends a learning session**

---

## Simple Summary

The attention score is a number from **0 to 100** that measures how seriously a user watched a video or read a PDF.

---

## Step 1 — Is it a PDF or a Video?

| Content Type | Formula |
|---|---|
| **PDF** | Attention = completion percentage (how much they scrolled through) |
| **Video** | Use the full formula below |

---

## Step 2 — Video Formula (starts at 50 points)

Every video session starts with **50 base points**, then adds or subtracts based on 3 things.

---

### Thing 1: Time Ratio (how long did they actually watch?)

> **Time Ratio = active playback time ÷ video duration**

| Time Ratio | Meaning | Points |
|---|---|---|
| 0.80 – 2.00 | Watched most or all of it | **+25** |
| 0.50 – 0.79 | Watched about half | **+10** |
| 0.30 – 0.49 | Watched very little | no change |
| **< 0.30** | Barely watched | **−25** |
| **> 2.00** | Watched way too fast (suspicious) | **−15** |

**Example:** 10-minute video, user watched for 9 minutes → ratio = 0.90 → **+25 points**

---

### Thing 2: Completion Percentage (how far did they get?)

| Completion | Points |
|---|---|
| **≥ 90%** | **+20** |
| **70% – 89%** | **+10** |
| **20% – 69%** | no change |
| **< 20%** | **−20** |

**Example:** User reached 95% of the video → **+20 points**

---

### Thing 3: Behavior (what did they do while watching?)

| Behavior | Threshold | Points |
|---|---|---|
| **Replayed sections** | 3 or more replays | **+5** (shows engagement) |
| **Skipped a lot** | More than 15 skips | **−15** |
| **Skipped moderately** | More than 8 skips | **−8** |
| **Changed speed often** | More than 3 speed changes | **−5** |

---

## Step 3 — Final Score

Add everything up, then clamp to the range **0–100**.

```
final_score = base(50) + time_ratio_points + completion_points + behavior_points

If result > 100 → set to 100
If result < 0  → set to 0
```

---

## Example Calculation

> User watches a 10-minute video:
> - Active time: 9 minutes → ratio = 0.90 → **+25**
> - Completion: 92% → **+20**
> - Replays: 4 times → **+5**
> - Skips: 3 times → no penalty
> - Speed changes: 1 time → no penalty

```
Score = 50 + 25 + 20 + 5 = 100
```

**Attention Score = 100 (Excellent)**

---

## Another Example (Low Engagement)

> User watches a 10-minute video:
> - Active time: 2 minutes → ratio = 0.20 → **−25**
> - Completion: 15% → **−20**
> - Skips: 18 times → **−15**

```
Score = 50 − 25 − 20 − 15 = -10 → clamped to 0
```

**Attention Score = 0 (No engagement)**

---

## Where the Score is Used in Reporting

| Report | How Attention is Used |
|---|---|
| **User Performance Report** | `avg_attention` = average of all session scores per user |
| **Performance Score** | One of 4 components (25% weight each) |
| **Risk Level** | `high` if avg_attention < 50, `medium` if < 70 |
| **KPI Dashboard** | Average attention across all users/courses |

---

## Risk Level Based on Attention

```
avg_attention < 50  →  risk = "high"
avg_attention < 70  →  risk = "medium"
avg_attention ≥ 70  →  risk = "low"
```

---

## What Gets Stored in the Database

**Table:** `learning_sessions`  
**Column:** `attention_score` (integer, 0–100)

Stored once per session when the user clicks **End Session** or the session closes automatically.
