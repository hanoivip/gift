<?php

namespace Hanoivip\Gift\Services;

class RewardTypes
{
    const BALANCE = "Balance";  // Balance point
    const TICKET = "Ticket";   // Ticket of doing some things
    const GAME_ITEMS = "Items";  // Game items, use when game code service unavailable
    const GAME_CODE = "GameCode"; // Code that need to forward into gameservice
}