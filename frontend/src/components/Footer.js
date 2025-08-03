import React from 'react';
import styled from 'styled-components';

const FooterContainer = styled.footer`
  background: rgba(0, 0, 0, 0.1);
  backdrop-filter: blur(10px);
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  padding: 20px 0;
  margin-top: auto;
`;

const FooterContent = styled.div`
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
  text-align: center;
`;

const FooterText = styled.p`
  color: rgba(255, 255, 255, 0.8);
  font-size: 14px;
  margin: 0;
  line-height: 1.6;
`;

const FooterLinks = styled.div`
  margin-top: 10px;
  display: flex;
  justify-content: center;
  gap: 20px;
  flex-wrap: wrap;
`;

const FooterLink = styled.a`
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  font-size: 12px;
  transition: color 0.2s ease;

  &:hover {
    color: white;
  }
`;

function Footer() {
  const currentYear = new Date().getFullYear();

  return (
    <FooterContainer>
      <FooterContent>
        <FooterText>
          © {currentYear} Poxica. Todos los derechos reservados.
        </FooterText>
        <FooterLinks>
          <FooterLink href="https://poxica.com" target="_blank" rel="noopener noreferrer">
            Ir a la tienda
          </FooterLink>
          <FooterLink href="https://poxica.com/contacto" target="_blank" rel="noopener noreferrer">
            Contacto
          </FooterLink>
          <FooterLink href="https://poxica.com/ayuda" target="_blank" rel="noopener noreferrer">
            Ayuda
          </FooterLink>
        </FooterLinks>
      </FooterContent>
    </FooterContainer>
  );
}

export default Footer;