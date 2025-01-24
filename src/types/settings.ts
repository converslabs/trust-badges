export type BadgeSize = 'extra-small' | 'small' | 'medium' | 'large';

export interface TrustBadgesSettings {
  showHeader: boolean;
  headerText: string;
  fontSize: string;
  alignment: 'left' | 'center' | 'right';
  badgeAlignment: 'left' | 'center' | 'right';
  textColor: string;
  badgeStyle: 'mono' | 'original' | 'mono-card' | 'card';
  badgeSizeDesktop: BadgeSize;
  badgeSizeMobile: BadgeSize;
  badgeColor: string;
  customMargin: boolean;
  marginTop: string;
  marginBottom: string;
  marginLeft: string;
  marginRight: string;
  animation: 'fade' | 'slide' | 'scale' | 'bounce';
  showOnProductPage: boolean;
  selectedBadges: string[];
} 