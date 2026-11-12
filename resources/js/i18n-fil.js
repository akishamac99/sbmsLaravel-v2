// Filipino (Tagalog) wording for the screens. One line per phrase: "English | Filipino".
// Common salon and payment words (booking, GCash, promo, walk-in) are kept because that is how people say them here.
const RAW = `
Dashboard | Dashboard
Notifications | Mga abiso
Manage Bookings | Pamahalaan ang mga booking
Schedule | Iskedyul
Walk-In | Walk-in
Walk-ins | Mga walk-in
Payments | Mga bayad
Attendance | Pagdalo
Customers | Mga kliyente
Services | Mga serbisyo
Promos | Mga promo
Service Categories | Mga kategorya ng serbisyo
Feedback | Puna
Reports | Mga ulat
Staff | Kawani
Staff Directory | Talaan ng kawani
Archived Accounts | Mga naka-archive na account
Staff Availability | Oras ng trabaho ng kawani
My Profile | Aking profile
Policies | Mga patakaran
Salon Hours | Oras ng salon
Permissions | Mga pahintulot
System Logs | Talaan ng aktibidad
My Bookings | Aking mga booking
My Feedback | Aking puna
My Customers | Aking mga kliyente
My Availability | Aking oras ng trabaho
Book a Service | Mag-book ng serbisyo
Book a service | Mag-book ng serbisyo
Logout | Mag-logout
Main | Pangunahin
Settings | Mga setting
Accessibility | Accessibility
Service | Serbisyo
Status | Katayuan
Actions | Mga aksyon
Action | Aksyon
Customer | Kliyente
Date | Petsa
Cancel | Kanselahin
Email | Email
Clear | I-clear
Close | Isara
Type | Uri
Contact number | Numero ng telepono
Contact | Kontak
Payment | Bayad
Delete | Burahin
Role | Tungkulin
Name | Pangalan
First name | Pangalan
Middle name | Gitnang pangalan
Last name | Apelyido
Full name | Buong pangalan
Discount | Diskwento
Amount | Halaga
Optional | Opsyonal
(optional) | (opsyonal)
All | Lahat
Bookings | Mga booking
Edit | I-edit
Category | Kategorya
Password | Password
Total | Kabuuan
View details | Tingnan ang detalye
View | Tingnan
Summary | Buod
Save | I-save
Save changes | I-save ang mga pagbabago
Home service | Serbisyo sa bahay
Home Service | Serbisyo sa bahay
Open | Buksan
Specialization | Espesyalisasyon
Add staff account | Magdagdag ng account ng kawani
Add staff | Magdagdag ng kawani
Method | Paraan
Time | Oras
Staff member | Kawani
Home address | Tirahan
Send payment proof | Ipadala ang patunay ng bayad
Send proof | Ipadala ang patunay
Reschedule | Ilipat ang iskedyul
Cancel booking | Kanselahin ang booking
Cancel this booking | Kanselahin ang booking na ito
Search bookings | Maghanap ng booking
Price | Presyo
Account name | Pangalan ng account
Remove | Alisin
Back to booking | Bumalik sa booking
Notes | Mga tala
Delete service | Burahin ang serbisyo
Username | Username
New promo | Bagong promo
Any status | Anumang katayuan
Approve | Aprubahan
Approve booking | Aprubahan ang booking
Working hours | Oras ng trabaho
Online bookings | Mga online booking
Mark completed | Markahang tapos na
Booking type | Uri ng booking
No bookings yet. | Wala pang booking.
No bookings match these filters. | Walang booking na tugma sa mga filter na ito.
No bookings match your search. | Walang booking na tugma sa iyong hinanap.
Completed bookings | Mga natapos na booking
Add category | Magdagdag ng kategorya
Add service | Magdagdag ng serbisyo
Description | Paglalarawan
Duration | Tagal
Gallery | Gallery
Event | Event
Email Address | Email address
Back to sign in | Bumalik sa pag-sign in
Verification code | Code ng beripikasyon
Appointment | Appointment
Date and time | Petsa at oras
Where | Saan
When | Kailan
Cancellation | Pagkansela
Pay online | Magbayad online
Bank transfer | Bank transfer
GCash number | GCash number
Mark All Read | Markahang nabasa na lahat
Mark Read | Markahang nabasa
No notifications yet. | Wala pang abiso.
Personal details | Personal na detalye
At least 8 characters. | Hindi bababa sa 8 karakter.
Update password | I-update ang password
Select Date | Pumili ng petsa
Select Time | Pumili ng oras
Select Staff | Pumili ng kawani
Choose date and time first | Pumili muna ng petsa at oras
Special Requests | Mga espesyal na kahilingan
Full | Buo
Recent Bookings | Mga kamakailang booking
Delete account | Burahin ang account
Details | Detalye
Search activity | Maghanap ng aktibidad
Downpayment | Downpayment
Restore account | Ibalik ang account
Restore | Ibalik
Save hours | I-save ang oras
Decline | Tanggihan
All roles | Lahat ng tungkulin
No staff match these filters. | Walang kawani na tugma sa mga filter na ito.
Search staff | Maghanap ng kawani
Active | Aktibo
Inactive | Hindi aktibo
Archive | I-archive
Manager | Manager
Owner | May-ari
Temporary password | Pansamantalang password
Present | Dumalo
No-show | Hindi sumipot
Proof | Patunay
Cash | Cash
GCash | GCash
Customer feedback | Puna ng mga kliyente
Account | Account
All types | Lahat ng uri
Search customer, phone, service or reference | Maghanap ng kliyente, telepono, serbisyo o reference
Registered | Nakarehistro
Not verified | Hindi pa na-verify
Account status | Katayuan ng account
Download report | I-download ang ulat
Back to report | Bumalik sa ulat
Print report | I-print ang ulat
Revenue | Kita
Average rating | Karaniwang rating
Today | Ngayon
Search services | Maghanap ng serbisyo
In service | Ginagawa na
Pending | Naghihintay
Approved | Aprubado
Completed | Tapos na
Declined | Tinanggihan
Cancelled | Kinansela
Confirmed | Nakumpirma
Paid | Bayad na
Rejected | Tinanggihan
Proof-submitted | Naipadala na ang patunay
Refunded | Naibalik ang bayad
Blocked | Naka-block
Verified | Na-verify
Unread notifications | Mga hindi pa nabasang abiso
Upcoming | Paparating
Story | Kuwento
Visit | Bisitahin
My dashboard | Aking dashboard
Login / Book | Mag-login / Mag-book
Go to my dashboard | Pumunta sa aking dashboard
Book appointment | Mag-book ng appointment
Create account | Gumawa ng account
Our Story | Aming kuwento
Quiet luxury, careful hands, and a deeply personal salon experience. | Tahimik na karangyaan, maingat na kamay, at personal na karanasan sa salon.
Clients served | Mga napagsilbihang kliyente
Beauty experts | Mga eksperto sa kagandahan
Days open | Mga araw na bukas
Service Menu | Listahan ng serbisyo
Beauty services for daily care and special moments. | Mga serbisyong pampaganda para sa araw-araw at mga espesyal na okasyon.
Popular Picks | Mga paboritong pinipili
Affordable favorites to begin with. | Mga abot-kayang paborito para magsimula.
View all services | Tingnan ang lahat ng serbisyo
Client Feedback | Puna ng mga kliyente
What clients say after their appointments. | Ano ang sinasabi ng mga kliyente matapos ang kanilang appointment.
Show more reviews | Magpakita ng higit pang review
Why Clients Return | Bakit bumabalik ang mga kliyente
Practical comforts that make booking easier. | Mga kaginhawahang nagpapadali ng pag-book.
Home service available | Available ang serbisyo sa bahay
Flexible hours | Flexible na oras
Experienced professionals | Mga bihasang propesyonal
Accessible pricing | Abot-kayang presyo
Visit Us | Bisitahin kami
Find us in Baguio City. | Matatagpuan kami sa Baguio City.
Book an appointment | Mag-book ng appointment
Open in Google Maps | Buksan sa Google Maps
Baguio's premium beauty destination | Premium na destinasyon ng kagandahan sa Baguio
Forgot | Nakalimutan
password? | ang password?
Forgot password? | Nakalimutan ang password?
Send code | Ipadala ang code
Welcome | Maligayang
back | pagbabalik
Sign in | Mag-sign in
Create an account | Gumawa ng account
Enter password | Ilagay ang password
Already have an account? | May account na?
Confirm password | Kumpirmahin ang password
Confirm new password | Kumpirmahin ang bagong password
New password | Bagong password
Change password | Palitan ang password
Send verification code | Ipadala ang verification code
Resend code | Ipadala muli ang code
Start over | Magsimulang muli
Verify code | I-verify ang code
Send a new code | Magpadala ng bagong code
Use a different email | Gumamit ng ibang email
Check your spam folder if it does not arrive within a minute. | Tingnan ang spam folder kung hindi dumating sa loob ng isang minuto.
The code is valid for 15 minutes. | May bisa ang code sa loob ng 15 minuto.
All users log in with their email address | Lahat ay nag-log in gamit ang kanilang email address
You log in with this address. | Ito ang gamit mo sa pag-log in.
Back to home | Bumalik sa home
Back to my bookings | Bumalik sa aking mga booking
Back to services | Bumalik sa mga serbisyo
Back to customers | Bumalik sa mga kliyente
Your notes | Iyong mga tala
Balance at the salon | Balanse sa salon
Send payment proof instead | Ipadala na lang ang patunay ng bayad
Need to change this booking? | Kailangang baguhin ang booking na ito?
Your upcoming and past appointments. | Iyong paparating at nakaraang mga appointment.
Rate service | I-rate ang serbisyo
Search by service or reference | Maghanap ayon sa serbisyo o reference
Checkout | Checkout
Your appointments | Iyong mga appointment
How much will you pay now? | Magkano ang babayaran mo ngayon?
Nothing more to pay on the day | Wala nang babayaran sa araw ng appointment
GCash / e-wallet | GCash / e-wallet
Bank | Bangko
Account number | Numero ng account
Attach your payment screenshot | I-attach ang screenshot ng bayad
Anything else? | May iba pa?
Notes for the salon | Mga tala para sa salon
Promo code | Promo code
Apply | Ilapat
Home service fee | Bayad sa serbisyo sa bahay
Pay now | Magbayad ngayon
Place booking | I-book na
Order summary | Buod ng order
Enter code | Ilagay ang code
Rate Your Experience | I-rate ang Iyong Karanasan
Waiting for your rating | Naghihintay ng iyong rating
Feedback sent | Naipadalang puna
Your average rating | Iyong karaniwang rating
To rate | Para i-rate
Completed appointments without feedback | Mga natapos na appointment na walang puna
Your comment | Iyong komento
Be specific - it helps the most. | Maging tiyak - malaking tulong ito.
Submit feedback | Ipadala ang puna
Your previous feedback | Iyong mga nakaraang puna
What went well? Anything we could do better? | Ano ang maganda? May maaari ba naming mapabuti?
How was the service? | Kumusta ang serbisyo?
How was your stylist / staff? | Kumusta ang iyong stylist / kawani?
Service rating | Rating ng serbisyo
Staff rating | Rating ng kawani
Comment | Komento
My Profile | Aking profile
Keep your details current so we can reach you about your appointments. | Panatilihing napapanahon ang iyong detalye para makontak ka namin tungkol sa iyong mga appointment.
Home address | Tirahan
Only needed for home-service bookings | Kailangan lamang para sa mga booking ng serbisyo sa bahay
New date | Bagong petsa
New time | Bagong oras
Move booking | Ilipat ang booking
Keep current time | Panatilihin ang kasalukuyang oras
Book Service | Mag-book ng serbisyo
Book a Service | Mag-book ng serbisyo
Pick a service, choose a time, then check out from your cart. | Pumili ng serbisyo, pumili ng oras, at mag-checkout mula sa iyong cart.
Service Catalog | Katalogo ng serbisyo
No services match your filters. | Walang serbisyong tugma sa iyong mga filter.
Event Service | Serbisyo para sa event
Home Service Available | Available ang serbisyo sa bahay
Booking Cart | Cart ng booking
In salon | Sa salon
Selected | Napili
Checkout selected | I-checkout ang napili
Add to Cart | Idagdag sa cart
Edit Cart Item | I-edit ang item sa cart
Search services... | Maghanap ng serbisyo...
Additional notes... | Karagdagang tala...
Later | Mamaya
Click to choose a screenshot, or drag it here | I-click para pumili ng screenshot, o i-drag dito
Choose payment screenshot | Pumili ng screenshot ng bayad
Last spot | Huling slot
Your pick | Iyong pinili
Choose a date to see the available times. | Pumili ng petsa para makita ang mga available na oras.
Your appointments and notifications in one place. | Iyong mga appointment at abiso sa iisang lugar.
Completed | Tapos na
Close message | Isara ang mensahe
Archived staff accounts | Mga naka-archive na account ng kawani
No archived staff accounts. | Walang naka-archive na account ng kawani.
No inactive or blocked customer accounts. | Walang hindi aktibo o naka-block na account ng kliyente.
Everyone | Lahat
Who | Sino
Activity | Aktibidad
No activity matches these filters. | Walang aktibidad na tugma sa mga filter na ito.
Account type | Uri ng account
Manager access | Access ng manager
Section | Seksyon
Can open | Maaaring buksan
Can do | Maaaring gawin
View only | Pagtingin lamang
Save access | I-save ang access
Booking policies | Mga patakaran sa booking
Cancellation window | Palugit sa pagkansela
Reschedules allowed | Pinapayagang paglipat ng iskedyul
Bookings per time slot | Mga booking bawat time slot
Save policies | I-save ang mga patakaran
Running now | Tumatakbo ngayon
Scheduled | Nakaiskedyul
Expired | Nag-expire na
Turned off | Naka-off
Valid | May bisa
Used | Nagamit na
No promos match these filters. | Walang promo na tugma sa mga filter na ito.
Discount type | Uri ng diskwento
Percentage (%) | Porsyento (%)
Minimum spend | Minimum na gastos
Discount cap | Limitasyon ng diskwento
Starts | Magsisimula
Ends | Magtatapos
Usage limit | Limitasyon ng paggamit
Save promo | I-save ang promo
Search code or name | Maghanap ng code o pangalan
Search promos | Maghanap ng promo
No cap | Walang limitasyon
Unlimited | Walang limitasyon
Opening hours | Oras ng pagbubukas
Availability requests | Mga kahilingan sa oras ng trabaho
Day | Araw
Current | Kasalukuyan
Requested | Hiniling
Working days | Mga araw ng trabaho
Hours | Oras
Not scheduled | Walang iskedyul
Varies by day | Nag-iiba bawat araw
Edit hours | I-edit ang oras
Note to staff (optional) | Tala sa kawani (opsyonal)
Note to staff | Tala sa kawani
Search name or specialization | Maghanap ng pangalan o espesyalisasyon
Search name, email or specialization | Maghanap ng pangalan, email o espesyalisasyon
Messages | Mga mensahe
Open chat | Buksan ang chat
Chat | Chat
Back | Bumalik
Close chat | Isara ang chat
Type a message... | Mag-type ng mensahe...
Send | Ipadala
Disconnect | Idiskonekta
Connect Messenger | Ikonekta ang Messenger
Show password | Ipakita ang password
Show | Ipakita
Awaiting | Naghihintay
No attendance records yet. | Wala pang tala ng pagdalo.
Edit booking | I-edit ang booking
Booked through | Na-book sa pamamagitan ng
Rescheduled | Inilipat ang iskedyul
Home service address | Address ng serbisyo sa bahay
Special request | Espesyal na kahilingan
Balance on the day | Balanse sa araw ng appointment
Paid so far | Nabayaran na
Still to pay | Natitirang babayaran
Record a payment | Mag-record ng bayad
Amount (PHP) | Halaga (₱)
Reference number | Reference number
Record payment | I-record ang bayad
Reason | Dahilan
The customer will see this. | Makikita ito ng kliyente.
Manage Bookings | Pamahalaan ang mga booking
Approve online requests and follow every appointment, online or walk-in. | Aprubahan ang mga online na kahilingan at subaybayan ang bawat appointment, online man o walk-in.
Assign staff | Magtalaga ng kawani
Booking status | Katayuan ng booking
Customers | Mga kliyente
Registered accounts and walk-in customers, with their booking history. | Mga rehistradong account at walk-in na kliyente, kasama ang kanilang kasaysayan ng booking.
About account status | Tungkol sa katayuan ng account
All customers | Lahat ng kliyente
Last visit | Huling bisita
Since | Mula
No login | Walang login
No customers match these filters. | Walang kliyenteng tugma sa mga filter na ito.
Search name, email or phone | Maghanap ng pangalan, email o telepono
Search customers | Maghanap ng kliyente
Customer type | Uri ng kliyente
Choose a file format | Pumili ng format ng file
PDF document | PDF na dokumento
Download | I-download
Update booking | I-update ang booking
Assigned staff | Nakatalagang kawani
Not assigned | Hindi pa nakatalaga
Keep as is | Panatilihin
Feedback could not be loaded. | Hindi ma-load ang puna.
Retry | Subukan muli
Search service or comment | Maghanap ng serbisyo o komento
Rating | Rating
All ratings | Lahat ng rating
Apply filters | Ilapat ang mga filter
No comment left. | Walang iniwang komento.
Rating summary | Buod ng rating
Filter feedback | I-filter ang puna
Customers you have been booked with, and their history with you. | Mga kliyenteng na-book sa iyo at ang kanilang kasaysayan sa iyo.
Visits with you | Mga bisita sa iyo
View history | Tingnan ang kasaysayan
No customers yet. | Wala pang kliyente.
Updates that need your attention | Mga update na kailangan ng iyong pansin
Received | Natanggap
View booking | Tingnan ang booking
Confirm payment | Kumpirmahin ang bayad
Reject proof | Tanggihan ang patunay
No payments yet. | Wala pang bayad.
Operational Report | Ulat ng operasyon
Printable report | Maaaring i-print na ulat
Operational Reports | Mga ulat ng operasyon
All statuses | Lahat ng katayuan
All staff | Lahat ng kawani
All services | Lahat ng serbisyo
Quick range: | Mabilisang saklaw:
Revenue by Service | Kita ayon sa serbisyo
Staff Performance | Performance ng kawani
Booking records | Mga tala ng booking
From date | Mula sa petsa
To date | Hanggang sa petsa
Show day | Ipakita ang araw
The salon has no open slots on this day. | Walang bukas na slot ang salon sa araw na ito.
Reserved | Naka-reserba
Off | Walang pasok
Choose a date | Pumili ng petsa
Service categories and the number of services in each | Mga kategorya ng serbisyo at bilang ng serbisyo sa bawat isa
Regular | Karaniwan
No categories yet. | Wala pang kategorya.
Category name | Pangalan ng kategorya
Save category | I-save ang kategorya
Active and inactive | Aktibo at hindi aktibo
Active only | Aktibo lamang
Inactive only | Hindi aktibo lamang
No services match these filters. | Walang serbisyong tugma sa mga filter na ito.
Service name | Pangalan ng serbisyo
Uncategorized | Walang kategorya
minutes | minuto
Photo | Larawan
Save service | I-save ang serbisyo
Service photo | Larawan ng serbisyo
Who is on the team and what they specialise in. | Sino ang nasa team at ano ang kanilang espesyalidad.
No staff members found. | Walang nakitang kawani.
Check in a customer who is at the salon now. No approval step is needed. | I-check in ang kliyenteng nasa salon ngayon. Hindi na kailangan ng pag-apruba.
Select a service | Pumili ng serbisyo
Time and staff | Oras at kawani
Payment at the desk | Bayad sa counter
Amount paid now | Halagang binayaran ngayon
First available | Unang available
Service price | Presyo ng serbisyo
Balance after service | Balanse pagkatapos ng serbisyo
Collect now | Kolektahin ngayon
Check in walk-in | I-check in ang walk-in
Walk-in summary | Buod ng walk-in
Total bookings | Kabuuang booking
Pending bookings | Mga booking na naghihintay
Completed revenue | Kita mula sa natapos
Feedback received | Natanggap na puna
Revenue by service | Kita ayon sa serbisyo
Staff performance | Performance ng kawani
No approved online bookings need attendance. | Walang aprubadong online booking na kailangang i-attendance.
Waiting for the owner. | Naghihintay sa may-ari.
Weekly working hours | Lingguhang oras ng trabaho
Reason for the change | Dahilan ng pagbabago
Send for approval | Ipadala para sa pag-apruba
Recent requests | Mga kamakailang kahilingan
Online bookings and walk-ins assigned to you. | Mga online booking at walk-in na nakatalaga sa iyo.
Record attendance | I-record ang pagdalo
No bookings are assigned to you. | Walang booking na nakatalaga sa iyo.
Please fix the following: | Pakiayos ang mga sumusunod:
Please confirm | Pakikumpirma
No, go back | Hindi, bumalik
Yes, continue | Oo, ituloy
Please wait... | Sandali lang...
Fill in the required fields to continue. | Punan ang mga kinakailangang field para magpatuloy.
Looks good. | Maayos.
Mobile number must have 11 digits (09XX XXX XXXX). | Ang mobile number ay dapat may 11 digit (09XX XXX XXXX).
Use 11 digits starting with 09 (09XX XXX XXXX). | Gumamit ng 11 digit na nagsisimula sa 09 (09XX XXX XXXX).
A Philippine mobile number starts with 09 (09XX XXX XXXX). | Ang mobile number sa Pilipinas ay nagsisimula sa 09 (09XX XXX XXXX).
This is taking longer than usual. Please check your internet connection. You can try again. | Mas matagal ito kaysa karaniwan. Pakisuri ang iyong internet connection. Maaari mong subukang muli.
Thank you for your feedback. | Salamat sa iyong puna.
A new OTP has been sent. | Naipadala na ang bagong code.
Account successfully deleted. | Matagumpay na nabura ang account.
Account successfully restored | Matagumpay na naibalik ang account
All notifications marked as read. | Namarkahang nabasa na ang lahat ng abiso.
Attendance recorded. | Naitala na ang pagdalo.
Booking approved. | Naaprubahan ang booking.
Booking cancelled. | Nakansela ang booking.
Booking declined. | Tinanggihan ang booking.
Booking not found. | Hindi nakita ang booking.
Booking updated. | Na-update ang booking.
Cart item updated. | Na-update ang item sa cart.
Customer account status updated. | Na-update ang katayuan ng account ng kliyente.
Item removed from your cart. | Naalis ang item sa iyong cart.
Manager permissions updated. | Na-update ang mga pahintulot ng manager.
Messenger disconnected. | Nadiskonekta ang Messenger.
Password updated. | Na-update ang password.
Payment recorded. | Naitala ang bayad.
Please enter a promo code first. | Maglagay muna ng promo code.
Please verify the code first. | I-verify muna ang code.
Profile updated. | Na-update ang profile.
Promo code applied. | Nailapat ang promo code.
Promo code removed. | Naalis ang promo code.
Promotion created. | Nagawa ang promo.
Promotion deleted. | Nabura ang promo.
Promotion updated. | Na-update ang promo.
Promotion status updated. | Na-update ang katayuan ng promo.
Request declined. | Tinanggihan ang kahilingan.
Salon hours saved. | Nai-save ang oras ng salon.
Service added to the catalog. | Naidagdag ang serbisyo sa katalogo.
Service added to your cart. | Naidagdag ang serbisyo sa iyong cart.
Service availability updated. | Na-update ang availability ng serbisyo.
Service category added. | Naidagdag ang kategorya ng serbisyo.
Service category deleted. | Nabura ang kategorya ng serbisyo.
Service category updated. | Na-update ang kategorya ng serbisyo.
Service updated. | Na-update ang serbisyo.
Staff account archived; appointment history was preserved. | Na-archive ang account ng kawani; napanatili ang kasaysayan ng appointment.
Staff account created. | Nagawa ang account ng kawani.
Staff account updated. | Na-update ang account ng kawani.
System policies updated. | Na-update ang mga patakaran ng sistema.
Walk-in marked as completed. | Namarkahang tapos na ang walk-in.
Payment proof uploaded successfully. Our team will verify your payment. | Matagumpay na na-upload ang patunay ng bayad. Ico-verify ng aming team ang iyong bayad.
Registration complete. Please sign in with your email. | Tapos na ang pagrerehistro. Mag-sign in gamit ang iyong email.
Password changed. Please log in with your new password. | Napalitan na ang password. Mag-log in gamit ang bago mong password.
This booking cannot be cancelled. | Hindi maaaring kanselahin ang booking na ito.
Walk-in bookings are currently disabled. | Kasalukuyang naka-off ang mga walk-in na booking.
Select at least one service in the cart before checkout. | Pumili ng kahit isang serbisyo sa cart bago mag-checkout.
Please settle your unpaid booking before proceeding to checkout. | Bayaran muna ang iyong hindi pa bayad na booking bago mag-checkout.
Do you want to leave? Changes you made may not be saved. | Gusto mo bang umalis? Maaaring hindi ma-save ang iyong mga pagbabago.
Changes apply at once and are remembered on this device. | Agad na nalalapat ang mga pagbabago at maaalala sa device na ito.
Text size | Laki ng teksto
Normal | Karaniwan
Large | Malaki
Largest | Pinakamalaki
Seeing | Paningin
High contrast (black and yellow) | Mataas na contrast (itim at dilaw)
Easy-to-read font | Madaling basahing font
More space between lines and letters | Mas maluwag na pagitan ng linya at letra
Underline all links | Lagyan ng guhit ang lahat ng link
Large mouse pointer | Malaking pointer ng mouse
Moving and clicking | Paggalaw at pag-click
Bigger buttons and click areas | Mas malalaking button at click area
Focus and reading | Pokus at pagbabasa
Stop animations | Itigil ang mga animation
Keep messages on screen until I close them | Panatilihin ang mga mensahe hanggang isara ko
Read page aloud | Basahin ang pahina
Reset all | I-reset lahat
Accessibility settings (Alt+A) | Mga setting ng accessibility (Alt+A)
Language | Wika
English | English
Filipino | Filipino
`;

export const dict = new Map();
RAW.split('\n').forEach((line) => {
    const i = line.indexOf(' | ');
    if (i > 0) dict.set(line.slice(0, i).trim().toLowerCase(), line.slice(i + 3).trim());
});

// Sentences with names or numbers in them.
export const patterns = [
    [/^Archive (.+)\? They will no longer be able to log in\.?.*$/i, (m) => `I-archive si ${m[1]}? Hindi na siya makaka-log in.`],
    [/^Delete the category (.+)\?.*$/i, (m) => `Burahin ang kategorya na ${m[1]}?`],
    [/^Delete promo (.+)\?.*$/i, (m) => `Burahin ang promo na ${m[1]}?`],
    [/^Decline booking (.+)\?.*$/i, (m) => `Tanggihan ang booking ${m[1]}?`],
    [/^Cancel booking (.+)\?.*$/i, (m) => `Kanselahin ang booking ${m[1]}? Aabisuhan ang kliyente.`],
    [/^Reject this payment.*$/i, () => 'Tanggihan ang patunay ng bayad na ito?'],
    [/^Mark this walk-in.*$/i, () => 'Markahang tapos na ang walk-in na ito?'],
    [/^Mark this customer.*no-show.*$/i, () => 'Markahang hindi sumipot ang kliyenteng ito?'],
    [/^Hello, (.+)$/i, (m) => `Kumusta, ${m[1]}`],
    [/^Welcome back, (.+)$/i, (m) => `Maligayang pagbabalik, ${m[1]}`],
    [/^(\d+) bookings?$/i, (m) => `${m[1]} booking`],
    [/^(\d+) reviews?$/i, (m) => `${m[1]} review`],
    [/^Showing (\d+) to (\d+) of (\d+) results?$/i, (m) => `Ipinapakita ang ${m[1]} hanggang ${m[2]} sa ${m[3]} resulta`],
];
