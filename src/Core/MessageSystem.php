<?php
// src/Core/MessageSystem.php

namespace App\Core;

class MessageSystem
{
    public static function setSuccess(string $message): void
    {
        $_SESSION['success_message'] = $message;
    }

    public static function setError(string $message): void
    {
        $_SESSION['error_message'] = $message;
    }

    public static function setInfo(string $message): void
    {
        $_SESSION['info_message'] = $message;
    }

    public static function setWarning(string $message): void
    {
        $_SESSION['warning_message'] = $message;
    }

    public static function getSuccess(): ?string
    {
        $message = $_SESSION['success_message'] ?? null;
        unset($_SESSION['success_message']);
        return $message;
    }

    public static function getError(): ?string
    {
        $message = $_SESSION['error_message'] ?? null;
        unset($_SESSION['error_message']);
        return $message;
    }

    public static function getInfo(): ?string
    {
        $message = $_SESSION['info_message'] ?? null;
        unset($_SESSION['info_message']);
        return $message;
    }

    public static function getWarning(): ?string
    {
        $message = $_SESSION['warning_message'] ?? null;
        unset($_SESSION['warning_message']);
        return $message;
    }

    public static function displayMessages(): string
    {
        $html = '';

        if ($success = self::getSuccess()) {
            $html .= '<div class="alert alert-success alert-dismissible fade show" role="alert">';
            $html .= '<i class="bi bi-check-circle-fill me-2"></i>';
            $html .= htmlspecialchars($success);
            $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            $html .= '</div>';
        }

        if ($error = self::getError()) {
            $html .= '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
            $html .= '<i class="bi bi-exclamation-circle-fill me-2"></i>';
            $html .= htmlspecialchars($error);
            $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            $html .= '</div>';
        }

        if ($info = self::getInfo()) {
            $html .= '<div class="alert alert-info alert-dismissible fade show" role="alert">';
            $html .= '<i class="bi bi-info-circle-fill me-2"></i>';
            $html .= htmlspecialchars($info);
            $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            $html .= '</div>';
        }

        if ($warning = self::getWarning()) {
            $html .= '<div class="alert alert-warning alert-dismissible fade show" role="alert">';
            $html .= '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
            $html .= htmlspecialchars($warning);
            $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            $html .= '</div>';
        }

        return $html;
    }
}