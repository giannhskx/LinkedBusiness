<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class AstronautController extends Controller
{
    public function getAstronautsByNationality(Request $request)
    {
        // Λήψη παραμέτρων από το αίτημα
        $nationality = $request->input('nationality');
        $inSpace = $request->input('in_space', false); // Προεπιλογή false αν δεν παρέχεται

        // Έλεγχος αν έχει δοθεί η παράμετρος nationality
        if (!$nationality) {
            return response()->json(['error' => 'Nationality parameter is required'], 400);
        }

        // Δημιουργία νέου GuzzleHttp client
        $client = new Client([
            'verify' => false, // Απενεργοποίηση επαλήθευσης SSL
            'timeout' => 10, // Ορισμός χρόνου αναμονής για κάθε αίτημα
        ]);

        $astronauts = []; // Πίνακας για αποθήκευση των αστροναυτών
        $page = 1; // Έναρξη από την πρώτη σελίδα
        $retryAfter = 0; // Μεταβλητή για αποθήκευση του χρόνου αναμονής σε περίπτωση rate limiting
        $perPage = 100; // Αριθμός αποτελεσμάτων ανά σελίδα

        while (true) {
            // Αν υπάρχει χρόνος αναμονής, αναμονή για τον συγκεκριμένο χρόνο
            if ($retryAfter > 0) {
                sleep($retryAfter);
                $retryAfter = 0;
            }

            try {
                // Εκτέλεση αιτήματος προς το API
                $response = $client->get('https://ll.thespacedevs.com/2.2.0/astronaut/', [
                    'query' => [
                        'format' => 'json',
                        'nationality' => $nationality,
                        'in_space' => $inSpace,
                        'limit' => $perPage,
                        'offset' => ($page - 1) * $perPage,
                    ],
                ]);

                // Έλεγχος για rate limiting
                if ($response->getStatusCode() === 429) {
                    $retryAfter = $response->getHeader('Retry-After')[0] ?? 60;
                    continue;
                }

                // Έλεγχος αν το αίτημα ήταν επιτυχές
                if ($response->getStatusCode() !== 200) {
                    Log::error('Error fetching astronauts data: ' . $response->getReasonPhrase());
                    return response()->json(['error' => 'Unable to fetch data. Please try again later.'], 500);
                }

                // Ανάγνωση δεδομένων από το API
                $data = json_decode($response->getBody()->getContents(), true);

                Log::info('Fetched data', ['page' => $page, 'results' => count($data['results'])]);

                // Έλεγχος αν τα αποτελέσματα είναι άδεια
                if (!isset($data['results']) || empty($data['results'])) {
                    break;
                }

                // Προσθήκη των αστροναυτών στον πίνακα
                foreach ($data['results'] as $astronaut) {
                    $astronauts[] = [
                        // 'count' => $data['count'], Την σχολιαζω ώστε να μην επιστρέφεται σε κάθε αστροναύτη
                        'id' => $astronaut['id'],
                        'name' => $astronaut['name'],
                        'age' => $astronaut['age'],
                        'bio' => $astronaut['bio'],
                        'twitter' => $astronaut['twitter'],
                        'instagram' => $astronaut['instagram'],
                        'wiki' => $astronaut['wiki'],
                        'url' => $astronaut['url'],
                        'agency' => [
                            'id' => $astronaut['agency']['id'],
                            'url' => $astronaut['agency']['url'],
                            'name' => $astronaut['agency']['name'],
                            'featured' => $astronaut['agency']['featured'],
                            'type' => $astronaut['agency']['type'],
                            'country_code' => $astronaut['agency']['country_code'],
                            'abbrev' => $astronaut['agency']['abbrev'],
                            'description' => $astronaut['agency']['description'],
                            'administrator' => $astronaut['agency']['administrator'],
                            'founding_year' => $astronaut['agency']['founding_year'],
                            'launchers' => $astronaut['agency']['launchers'],
                            'spacecraft' => $astronaut['agency']['spacecraft'],
                            'parent' => $astronaut['agency']['parent'],
                            'image_url' => $astronaut['agency']['image_url'],
                            'logo_url' => $astronaut['agency']['logo_url'],
                        ],
                        'profile_image' => $astronaut['profile_image'],
                        'profile_image_thumbnail' => $astronaut['profile_image_thumbnail'],
                        'flights_count' => $astronaut['flights_count'],
                        'landings_count' => $astronaut['landings_count'],
                        'spacewalks_count' => $astronaut['spacewalks_count'],
                        'last_flight' => $astronaut['last_flight'],
                        'first_flight' => $astronaut['first_flight'],
                    ];
                }

                $page++; // Αύξηση του αριθμού σελίδας για το επόμενο αίτημα

                // Έλεγχος αν έχουν επιστραφεί λιγότερα αποτελέσματα από το αναμενόμενο, τερματίζει την επανάληψη
                if (count($data['results']) < $perPage) {
                    break;
                }

            } catch (\Exception $e) {
                Log::error('Error fetching astronauts data: ' . $e->getMessage());
                return response()->json(['error' => 'Unable to fetch data. Please try again later.'], 500);
            }
        }

        return response()->json($astronauts);
    }
}
