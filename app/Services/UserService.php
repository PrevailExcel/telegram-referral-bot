<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    public function createAndReward($referrer, $bot)
    {
        $referralId = $bot->getUser()->getId();

        // Check if Referrer exists
        $referrer = User::whereReferralId($referrer)->first();
        if ($referrer) {

            // Create User if he doesn't exist
            $user = User::whereReferralId($referralId)->exists();

            if (!$user) {
                $user = User::create([
                    'name' => $bot->getUser()->getUsername(),
                    'referrer_id' => $referrer,
                    'referral_id' => $referralId
                ]);

                // Reward Referrer
                $referrer->increment('points', 100);
                $referrer->save();

                $response = [
                    'link' => $this->generateReferralLink($referralId),
                    'referrer_points' => $referrer->points
                ];

                return $response;
            } else {
                $response = [
                    'user_existed' => true,
                    'link' => $this->generateReferralLink($referralId)
                ];
                return $response;
            }
        } else
            return false;
    }

    public function create($bot)
    {
        $referralId = $bot->getUser()->getId();

        // Create User if he doesn't exist
        $user = User::whereReferralId($referralId)->exists();

        if (!$user)
            $user = User::create([
                'name' => $bot->getUser()->getUsername(),
                'referral_id' => $referralId
            ]);

        return $this->generateReferralLink($referralId);
    }

    public function getPoints($bot)
    {
        $referralId = $bot->getUser()->getId();
        $user = User::whereReferralId($referralId)->first();
        return   $user->points;
    }

    private function generateReferralLink($userId)
    {
        $telegramUsername = 'Prima_AI_bot';
        $referralLink = "https://t.me/$telegramUsername?start=$userId";

        return $referralLink;
    }
}
