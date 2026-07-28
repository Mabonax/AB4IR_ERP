import { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return <img {...props} src={props.src ?? '/poa-assets/poa-logo-icon.png'} alt={props.alt ?? 'POA logo'} />;
}
