# Enrollment identity checks

No migration is needed. Deploy together with Face Attendance's three-frame checks.

Enrollment checks the submitted embedding against active employees whose branch access
overlaps the employee being enrolled, including `all_branch` employees. A match at or above
`FACE_RECOGNITION_THRESHOLD` (default 0.45) is rejected for administrative identity review.
The response does not disclose the other employee's identity. Private logs contain the
involved employee/sample IDs and score. Existing samples are never deleted automatically.

Additional samples must match a strict majority of the employee's existing valid samples,
using the same configured threshold. Previously only the single best sample was checked
at the lower 0.363 enrollment threshold. Enrollment collision checks are serialized under
a shared branch row lock; inference occurs before acquiring the lock.

After deployment run `php artisan optimize:clear`. Configure the same
`FACE_RECOGNITION_THRESHOLD` in Master and Face Attendance; no production `.env` is edited
by this change. The value is a similarity cutoff, not an accuracy percentage. It has not
been recalibrated from the reported incident.

## Read-only audit

```
php artisan face:audit-enrollments
php artisan face:audit-enrollments --employee=16
```

Run on the Master server with its configured `face_enrollment` connection. The output
contains employee codes/IDs, branches, sample IDs and comparison scores; keep it internal.
It reports similar samples belonging to different active employees across the configured
database, including profiles with non-overlapping branches. It changes no records and
does not call the face service. The audit is quadratic in the number of samples.

Matching vectors are a review signal, not proof of duplicate or incorrect identity.
The reported incident still needs the two employee identities, branch and approximate
time to locate the attendance evidence. Enrollment capture photos are not retained by
the existing enrollment flow, so an embedding audit alone cannot prove who was enrolled.

Card/PIN verification and any manual identity bypass remain deferred.
