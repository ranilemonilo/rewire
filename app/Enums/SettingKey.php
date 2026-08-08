<?php

namespace App\Enums;

enum SettingKey: string
{
    case CompanyName = 'company_name';
    case CompanyTagline = 'company_tagline';
    case CompanyVision = 'company_vision';
    case CompanyMission = 'company_mission';
    case CompanyYearsExperience = 'company_years_experience';
    case CompanyTotalClients = 'company_total_clients';
    case CompanyTotalProjects = 'company_total_projects';
    case SeoDescription = 'seo_description';
    case AnalyticsId = 'analytics_id';
    case SocialLinkedin = 'social_linkedin';
    case SocialTwitter = 'social_twitter';
    case SocialGithub = 'social_github';
    case SocialInstagram = 'social_instagram';
    case ContactAddress = 'contact_address';
    case ContactEmail = 'contact_email';
    case ContactPhone = 'contact_phone';

    public function label(): string
    {
        return match ($this) {
            self::CompanyName => 'Company name',
            self::CompanyTagline => 'Company tagline',
            self::CompanyVision => 'Company vision',
            self::CompanyMission => 'Company mission',
            self::CompanyYearsExperience => 'Years of experience',
            self::CompanyTotalClients => 'Total clients',
            self::CompanyTotalProjects => 'Total projects',
            self::SeoDescription => 'SEO meta description',
            self::AnalyticsId => 'Google Analytics measurement ID',
            self::SocialLinkedin => 'LinkedIn URL',
            self::SocialTwitter => 'Twitter / X URL',
            self::SocialGithub => 'GitHub URL',
            self::SocialInstagram => 'Instagram URL',
            self::ContactAddress => 'Contact address',
            self::ContactEmail => 'Contact email',
            self::ContactPhone => 'Contact phone',
        };
    }
}